import './bootstrap';

const page = document.documentElement;
const siteHeader = document.querySelector('[data-site-header]');

const syncStickyHeader = () => {
    siteHeader?.classList.toggle('is-scrolled', window.scrollY > 4);
};

syncStickyHeader();
window.addEventListener('scroll', syncStickyHeader, { passive: true });

const themeStorageKey = 'shopkey-theme';

const applyTheme = (theme, persist = true) => {
    const selectedTheme = theme === 'light' ? 'light' : 'dark';
    const isDark = selectedTheme === 'dark';

    page.dataset.theme = selectedTheme;
    page.style.colorScheme = selectedTheme;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', isDark ? '#0b0b0c' : '#ffffff');

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        const actionLabel = isDark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối';
        const visibleLabel = isDark ? 'Giao diện tối' : 'Giao diện sáng';

        toggle.setAttribute('aria-label', actionLabel);
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute('title', actionLabel);

        const label = toggle.querySelector('[data-theme-label]');
        if (label) label.textContent = visibleLabel;
    });

    if (persist) {
        try {
            window.localStorage.setItem(themeStorageKey, selectedTheme);
        } catch (_) {
            // The theme still works for this page if browser storage is unavailable.
        }
    }
};

applyTheme(page.dataset.theme, false);

document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        applyTheme(page.dataset.theme === 'dark' ? 'light' : 'dark');
    });
});

window.addEventListener('storage', (event) => {
    if (event.key === themeStorageKey && ['light', 'dark'].includes(event.newValue)) {
        applyTheme(event.newValue, false);
    }
});

const mobileMenu = document.querySelector('[data-mobile-menu]');
const mobileBackdrop = document.querySelector('[data-mobile-menu-backdrop]');
const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');

const setMobileMenu = (open) => {
    if (!mobileMenu || !mobileBackdrop || !mobileMenuToggle) return;

    mobileMenu.hidden = !open;
    mobileBackdrop.hidden = !open;
    mobileMenuToggle.setAttribute('aria-expanded', String(open));
    page.classList.toggle('has-overlay', open);
};

mobileMenuToggle?.addEventListener('click', () => setMobileMenu(true));
document.querySelector('[data-mobile-menu-close]')?.addEventListener('click', () => setMobileMenu(false));
mobileBackdrop?.addEventListener('click', () => setMobileMenu(false));

const filterPanel = document.querySelector('[data-filter-panel]');

const setFilterPanel = (open) => {
    if (!filterPanel) return;

    filterPanel.classList.toggle('is-open', open);
    page.classList.toggle('has-filter-overlay', open);
};

document.querySelector('[data-filter-toggle]')?.addEventListener('click', () => setFilterPanel(true));
document.querySelector('[data-filter-close]')?.addEventListener('click', () => setFilterPanel(false));

document.querySelectorAll('[data-sort-select]').forEach((select) => {
    select.addEventListener('change', () => {
        const destination = new URL(window.location.href);
        destination.searchParams.set('sort', select.value);
        destination.searchParams.delete('page');
        window.location.assign(destination);
    });
});

document.querySelectorAll('.quantity-control').forEach((control) => {
    const input = control.querySelector('[data-qty-input]');
    const minus = control.querySelector('[data-qty-minus]');
    const plus = control.querySelector('[data-qty-plus]');

    if (!input) return;

    const changeQuantity = (amount) => {
        const minimum = Number(input.min || 1);
        const maximum = Number(input.max || Number.MAX_SAFE_INTEGER);
        const current = Number(input.value || minimum);
        input.value = Math.min(maximum, Math.max(minimum, current + amount));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    minus?.addEventListener('click', () => changeQuantity(-1));
    plus?.addEventListener('click', () => changeQuantity(1));
});

document.querySelectorAll('[data-gallery-image]').forEach((thumbnail) => {
    thumbnail.addEventListener('click', () => {
        const mainImage = document.querySelector('.product-gallery__main .product-art__image');

        if (!mainImage) return;

        mainImage.src = thumbnail.dataset.galleryImage;
        mainImage.alt = thumbnail.dataset.galleryAlt || mainImage.alt;
        document.querySelectorAll('[data-gallery-image]').forEach((item) => item.classList.remove('is-active'));
        thumbnail.classList.add('is-active');
    });
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const input = document.getElementById(toggle.dataset.passwordToggle);

        if (!input) return;

        const revealing = input.type === 'password';
        input.type = revealing ? 'text' : 'password';
        toggle.textContent = revealing ? 'Ẩn' : 'Hiện';
        toggle.setAttribute('aria-pressed', String(revealing));
    });
});

const adminSidebar = document.querySelector('[data-admin-sidebar]');
const adminBackdrop = document.querySelector('[data-admin-sidebar-backdrop]');

const setAdminSidebar = (open) => {
    if (!adminSidebar || !adminBackdrop) return;

    adminSidebar.classList.toggle('is-open', open);
    adminBackdrop.hidden = !open;
    page.classList.toggle('has-admin-overlay', open);
};

document.querySelector('[data-admin-menu-toggle]')?.addEventListener('click', () => setAdminSidebar(true));
adminBackdrop?.addEventListener('click', () => setAdminSidebar(false));

const inventoryBulkbar = document.querySelector('[data-inventory-bulkbar]');

if (inventoryBulkbar) {
    const selectAll = inventoryBulkbar.querySelector('[data-inventory-select-all]');
    const deleteButton = inventoryBulkbar.querySelector('[data-inventory-bulk-delete]');
    const selectedCount = inventoryBulkbar.querySelector('[data-inventory-selected-count]');
    const inventoryCheckboxes = [...document.querySelectorAll('[data-inventory-checkbox]')];

    const syncInventorySelection = () => {
        const checkedCount = inventoryCheckboxes.filter((checkbox) => checkbox.checked).length;

        if (selectedCount) selectedCount.textContent = String(checkedCount);
        if (deleteButton) deleteButton.disabled = checkedCount === 0;

        if (selectAll) {
            selectAll.checked = inventoryCheckboxes.length > 0 && checkedCount === inventoryCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < inventoryCheckboxes.length;
            selectAll.disabled = inventoryCheckboxes.length === 0;
        }
    };

    selectAll?.addEventListener('change', () => {
        inventoryCheckboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        syncInventorySelection();
    });

    inventoryCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncInventorySelection));
    syncInventorySelection();
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    setMobileMenu(false);
    setFilterPanel(false);
    setAdminSidebar(false);
});

document.querySelectorAll('[data-copy-value]').forEach((button) => {
    button.addEventListener('click', async () => {
        const original = button.textContent;

        try {
            await navigator.clipboard.writeText(button.dataset.copyValue || '');
            button.textContent = 'Đã sao chép';
        } catch {
            button.textContent = 'Không thể sao chép';
        }

        window.setTimeout(() => { button.textContent = original; }, 1600);
    });
});

document.querySelectorAll('[data-bank-quick-amounts] [data-amount]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.closest('form')?.querySelector('input[name="amount"]');

        if (input) {
            input.value = button.dataset.amount;
            input.focus();
        }
    });
});

const bankInvoice = document.querySelector('[data-bank-deposit-status-url]');

if (bankInvoice && ['pending', 'review'].includes(bankInvoice.dataset.bankDepositCurrentStatus)) {
    const poll = window.setInterval(async () => {
        try {
            const response = await fetch(bankInvoice.dataset.bankDepositStatusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const data = await response.json();
            const label = bankInvoice.querySelector('[data-bank-status-label]');

            if (label) label.textContent = data.status_label;

            if (data.status === 'paid') {
                window.clearInterval(poll);
                window.setTimeout(() => window.location.reload(), 700);
            }

            if (['expired', 'cancelled'].includes(data.status)) {
                window.clearInterval(poll);
            }
        } catch {
            // A temporary network error is harmless; the next poll retries.
        }
    }, 4000);
}

document.querySelectorAll('[data-site-dropzone]').forEach((dropzone) => {
    const input = dropzone.querySelector('[data-site-asset-input]');
    const card = dropzone.closest('.site-asset-card');
    const preview = card?.querySelector('[data-site-asset-preview]');
    const empty = card?.querySelector('[data-site-preview-empty]');
    const fileName = dropzone.querySelector('[data-site-file-name]');

    if (!input) return;

    const renderFile = (file) => {
        if (!file) return;

        if (fileName) fileName.textContent = file.name;
        dropzone.classList.add('has-file');

        if (preview && file.type.startsWith('image/')) {
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            empty?.setAttribute('hidden', '');
        }
    };

    input.addEventListener('change', () => renderFile(input.files?.[0]));
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');

        if (event.dataTransfer?.files?.length) {
            input.files = event.dataTransfer.files;
            renderFile(input.files[0]);
        }
    });
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

const supportRequest = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...(options.headers || {}),
        },
        ...options,
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const validationError = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(validationError || data.message || 'Không thể kết nối bộ phận hỗ trợ.');
    }

    return data;
};

const createChatMessage = (message, adminView = false) => {
    const article = document.createElement('article');
    article.classList.toggle('is-mine', Boolean(message.mine));
    article.classList.toggle('is-admin', Boolean(message.is_admin));
    const bubble = document.createElement('div');
    const sender = document.createElement('strong');
    const body = document.createElement('p');
    const time = document.createElement('small');

    sender.textContent = message.sender_name;
    body.textContent = message.message;
    time.textContent = message.created_at;
    bubble.append(sender, body, time);
    article.append(bubble);

    if (adminView) article.classList.add('admin-support-message');

    return article;
};

const supportChat = document.querySelector('[data-support-chat]');
const supportToggle = document.querySelector('[data-support-chat-toggle]');
let supportPoll = null;

if (supportChat && supportToggle) {
    const messagesBox = supportChat.querySelector('[data-support-chat-messages]');
    const form = supportChat.querySelector('[data-support-chat-form]');
    const textarea = form?.querySelector('textarea');
    const errorBox = supportChat.querySelector('[data-support-chat-error]');

    const showError = (message = '') => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.hidden = !message;
    };

    const loadMessages = async () => {
        const data = await supportRequest(supportChat.dataset.messagesUrl);

        if (!messagesBox) return;

        messagesBox.replaceChildren();

        if (!data.messages.length) {
            const empty = document.createElement('div');
            empty.className = 'support-chat__welcome';
            empty.innerHTML = '<strong>ShopKey có thể giúp gì cho bạn?</strong><span>Hãy mô tả vấn đề về đơn hàng, sản phẩm hoặc nạp tiền. Chỉ bạn và quản trị viên xem được cuộc trò chuyện này.</span>';
            messagesBox.append(empty);
        } else {
            data.messages.forEach((message) => messagesBox.append(createChatMessage(message)));
        }

        const closed = data.conversation?.status === 'closed';
        if (form) form.hidden = closed;
        showError(closed ? 'Cuộc trò chuyện đã đóng. Quản trị viên có thể mở lại khi cần.' : '');
        messagesBox.scrollTop = messagesBox.scrollHeight;
    };

    const openChat = async () => {
        supportChat.hidden = false;
        supportToggle.setAttribute('aria-expanded', 'true');
        document.querySelector('[data-support-unread-badge]')?.remove();
        showError('');

        try {
            await loadMessages();
            await supportRequest(supportChat.dataset.readUrl, { method: 'POST', body: '{}' });
        } catch (error) {
            showError(error.message);
        }

        textarea?.focus();
        window.clearInterval(supportPoll);
        supportPoll = window.setInterval(() => loadMessages().catch(() => {}), 4000);
    };

    const closeChat = () => {
        supportChat.hidden = true;
        supportToggle.setAttribute('aria-expanded', 'false');
        window.clearInterval(supportPoll);
    };

    supportToggle.addEventListener('click', openChat);
    supportChat.querySelector('[data-support-chat-close]')?.addEventListener('click', closeChat);
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = textarea?.value.trim();

        if (!message) return;

        form.classList.add('is-sending');
        showError('');

        try {
            await supportRequest(supportChat.dataset.sendUrl, {
                method: 'POST',
                body: JSON.stringify({ message }),
            });
            textarea.value = '';
            await loadMessages();
        } catch (error) {
            showError(error.message);
        } finally {
            form.classList.remove('is-sending');
            textarea?.focus();
        }
    });
}

const adminSupportMessages = document.querySelector('[data-admin-support-messages]');
const adminSupportReply = document.querySelector('[data-admin-support-reply]');

if (adminSupportMessages) {
    const loadAdminMessages = async () => {
        const data = await supportRequest(adminSupportMessages.dataset.messagesUrl);
        adminSupportMessages.replaceChildren();
        data.messages.forEach((message) => adminSupportMessages.append(createChatMessage(message, true)));
        adminSupportMessages.scrollTop = adminSupportMessages.scrollHeight;
    };

    window.setTimeout(() => { adminSupportMessages.scrollTop = adminSupportMessages.scrollHeight; }, 50);
    window.setInterval(() => loadAdminMessages().catch(() => {}), 4000);

    adminSupportReply?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const textarea = adminSupportReply.querySelector('textarea');
        const message = textarea?.value.trim();

        if (!message) return;

        try {
            await supportRequest(adminSupportReply.action, {
                method: 'POST',
                body: JSON.stringify({ message }),
            });
            textarea.value = '';
            await loadAdminMessages();
        } catch (error) {
            window.alert(error.message);
        }
    });
}
