<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Requests\Admin\WalletAdjustmentRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('wallet')
            ->withCount(['orders', 'cardDeposits'])
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->when($request->string('state')->value() === 'locked', fn (Builder $query) => $query->whereNotNull('locked_at'))
            ->when($request->string('state')->value() === 'active', fn (Builder $query) => $query->whereNull('locked_at'))
            ->when($request->string('role')->value(), fn (Builder $query, string $role) => $query->where('role', $role))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => UserRole::Customer]),
        ]);
    }

    public function store(UserRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => $data['role'],
                'email_verified_at' => now(),
                'locked_at' => $data['status'] === 'locked' ? now() : null,
            ]);

            $user->wallet()->create(['balance' => 0, 'currency' => 'VND']);
            $user->cart()->create();

            return $user;
        });

        $logger->log($request, 'admin.user.created', $user, [
            'role' => $user->role->value,
            'status' => $user->locked_at ? 'locked' : 'active',
        ]);

        return redirect()->route('admin.users.show', $user)->with('status', 'Đã tạo người dùng mới.');
    }

    public function show(User $user): View
    {
        $user->load([
            'wallet.transactions' => fn ($query) => $query->latest()->limit(10),
            'orders' => fn ($query) => $query->latest()->limit(10),
            'cardDeposits' => fn ($query) => $query->latest()->limit(10),
        ])->loadCount(['orders', 'cardDeposits']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $newRole = UserRole::from($data['role']);
        $this->ensureAdminContinuity($request, $user, $newRole, $data['status']);

        $changes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $newRole,
            'locked_at' => $data['status'] === 'locked' ? ($user->locked_at ?? now()) : null,
        ];

        if (filled($data['password'] ?? null)) {
            $changes['password'] = $data['password'];
        }

        $user->update($changes);
        $logger->log($request, 'admin.user.updated', $user, [
            'role' => $user->role->value,
            'status' => $user->locked_at ? 'locked' : 'active',
            'password_changed' => array_key_exists('password', $changes),
        ]);

        return redirect()->route('admin.users.show', $user)->with('status', 'Đã cập nhật người dùng.');
    }

    public function destroy(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'Bạn không thể xóa chính tài khoản đang đăng nhập.']);
        }

        if ($user->role === UserRole::Admin && User::query()->where('role', UserRole::Admin)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'Không thể xóa quản trị viên cuối cùng của hệ thống.']);
        }

        $hasAuditHistory = $user->orders()->exists()
            || $user->walletTransactions()->exists()
            || $user->cardDeposits()->exists()
            || $user->bankDeposits()->exists()
            || $user->reviews()->exists()
            || $user->supportConversation()->exists()
            || $user->supportMessages()->exists()
            || $user->wallet()->where('balance', '!=', 0)->exists();

        if ($hasAuditHistory) {
            throw ValidationException::withMessages([
                'user' => 'Không thể xóa người dùng đã có đơn hàng hoặc giao dịch. Hãy chuyển trạng thái sang Đã khóa để giữ dữ liệu đối soát.',
            ]);
        }

        $logger->log($request, 'admin.user.deleted', $user, [
            'email' => $user->email,
            'role' => $user->role->value,
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Đã xóa người dùng.');
    }

    public function toggleLock(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        abort_if($user->id === $request->user()->id || $user->role === UserRole::Admin, 422, 'Không thể khóa tài khoản quản trị.');

        $locking = $user->locked_at === null;
        $user->forceFill(['locked_at' => $locking ? now() : null])->save();
        $logger->log($request, $locking ? 'admin.user.locked' : 'admin.user.unlocked', $user);

        return back()->with('status', $locking ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.');
    }

    public function adjustWallet(WalletAdjustmentRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        $amount = $request->integer('amount');

        DB::transaction(function () use ($request, $user, $amount): void {
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0, 'currency' => 'VND']);
            $before = $wallet->balance;
            $after = $before + $amount;

            if ($after < 0) {
                throw ValidationException::withMessages(['amount' => 'Số dư sau điều chỉnh không thể âm.']);
            }

            $wallet->update(['balance' => $after]);
            $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => WalletTransactionType::AdminAdjustment,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'idempotency_key' => 'admin-adjustment-'.Str::uuid(),
                'description' => $request->string('reason')->value(),
                'status' => TransactionStatus::Completed,
            ]);
        });

        $logger->log($request, 'admin.wallet.adjusted', $user, ['amount' => $amount, 'reason' => $request->string('reason')->value()]);

        return back()->with('status', 'Đã điều chỉnh ví và ghi giao dịch đối soát.');
    }

    private function ensureAdminContinuity(Request $request, User $user, UserRole $newRole, string $status): void
    {
        if ($user->is($request->user()) && ($newRole !== UserRole::Admin || $status !== 'active')) {
            throw ValidationException::withMessages([
                'role' => 'Bạn không thể tự hạ vai trò hoặc khóa chính tài khoản đang đăng nhập.',
            ]);
        }

        $removesActiveAdmin = $user->role === UserRole::Admin
            && $user->locked_at === null
            && ($newRole !== UserRole::Admin || $status === 'locked');

        if ($removesActiveAdmin && User::query()->where('role', UserRole::Admin)->whereNull('locked_at')->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Hệ thống phải luôn còn ít nhất một quản trị viên đang hoạt động.',
            ]);
        }
    }
}
