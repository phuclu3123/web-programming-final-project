/* =========================================
   COUPONHUB - USER INTERACTIONS (JS)
========================================= */

document.addEventListener('DOMContentLoaded', function () {
    initFavorites();
    initMobileMenu();
    initAuthModal();
    initLiveSearch();
    initCopyDetailCode();
    initComments();
    initSubmitCouponForm();
    initMyCoupons();
});

/* ===== 1. CHỨC NĂNG SAO CHÉP MÃ COUPON ===== */

// Hàm sao chép dùng chung cho nút "Sao chép" ở thẻ Coupon
function copyCoupon(btnElement, code) {
    if (!code) return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(function () {
            showCopyFeedback(btnElement);
        }).catch(function () {
            fallbackCopyTextToClipboard(code, btnElement);
        });
    } else {
        fallbackCopyTextToClipboard(code, btnElement);
    }
}

// Fallback tương thích cho các trình duyệt hoặc môi trường HTTP (localhost)
function fallbackCopyTextToClipboard(text, btnElement) {
    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.top = '0';
    textArea.style.left = '0';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        showCopyFeedback(btnElement);
    } catch (err) {
        alert('Không thể sao chép tự động. Mã của bạn là: ' + text);
    }

    document.body.removeChild(textArea);
}

// Hiệu ứng phản hồi chữ trên nút khi sao chép thành công
function showCopyFeedback(btnElement) {
    if (!btnElement) return;

    var originalText = btnElement.innerHTML;
    btnElement.innerHTML = 'Đã chép! ✓';
    btnElement.style.background = '#18834b';
    btnElement.style.color = '#ffffff';
    btnElement.style.borderColor = '#18834b';

    setTimeout(function () {
        btnElement.innerHTML = originalText;
        btnElement.style.background = '';
        btnElement.style.color = '';
        btnElement.style.borderColor = '';
    }, 2000);
}

// Gắn sự kiện sao chép cho trang Chi tiết Coupon (coupon_detail.php)
function initCopyDetailCode() {
    var copyBtn = document.getElementById('copyDetailCode');
    var codeElem = document.getElementById('couponCode');

    if (copyBtn && codeElem) {
        copyBtn.addEventListener('click', function () {
            var code = codeElem.innerText.trim();
            copyCoupon(copyBtn, code);
        });
    }
}

/* ===== QUẢN LÝ YÊU THÍCH (RENDER ĐỘNG BẰNG JS) ===== */

function getFavorites() {
    return JSON.parse(localStorage.getItem('couponhub_favorites') || '[]');
}

function saveFavorites(favs) {
    localStorage.setItem('couponhub_favorites', JSON.stringify(favs));
}

function initFavorites() {
    var favs = getFavorites();

    // Đổi trạng thái nút thả tim trên các trang khác (Trang chủ, Chi tiết,...)
    document.querySelectorAll('[data-favorite-code]').forEach(function (btn) {
        var code = btn.getAttribute('data-favorite-code');
        if (favs.includes(code)) {
            btn.classList.add('active');
            var textSpan = btn.querySelector('.fav-text');
            if (textSpan) textSpan.textContent = 'Đã lưu';
        }

        if (!btn.dataset.favBound) {
            btn.dataset.favBound = 'true';
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleFavorite(code, btn);
            });
        }
    });

    // Nếu đang ở trang favorites.php thì tiến hành render đúng danh sách
    if (document.getElementById('favoriteCouponList')) {
        renderFavoritesPage();
    }
}

// Hàm render duy nhất các mã đã thả tim từ localStorage
function renderFavoritesPage() {
    var list = document.getElementById('favoriteCouponList');
    var emptyNotice = document.getElementById('emptyFavoriteNotice');
    if (!list) return;

    var favs = getFavorites();
    var allCoupons = window.ALL_COUPONS || [];

    // Lọc duy nhất các mã có trong mảng favorites
    var favCoupons = allCoupons.filter(function (item) {
        return favs.includes(item.code);
    });

    // Nếu mảng rỗng -> Hiện thông báo chưa có mã yêu thích
    if (favCoupons.length === 0) {
        list.innerHTML = '';
        if (emptyNotice) emptyNotice.style.display = 'block';
        return;
    }

    if (emptyNotice) emptyNotice.style.display = 'none';

    var html = '';
    favCoupons.forEach(function (coupon) {
        html += `
            <article 
                class="category-coupon-card" 
                id="coupon-card-${escapeHtml(coupon.code)}"
                data-coupon-code="${escapeHtml(coupon.code)}"
            >
                <div class="category-coupon-top">
                    <span class="category-store-name">${escapeHtml(coupon.store)}</span>

                    <button 
                        type="button" 
                        class="favorite-toggle-btn active" 
                        data-favorite-code="${escapeHtml(coupon.code)}"
                        onclick="toggleFavorite('${escapeHtml(coupon.code)}', this)"
                    >
                        ❤️ <span class="fav-text">Đã lưu</span>
                    </button>
                </div>

                <span class="category-coupon-discount">${escapeHtml(coupon.discount)}</span>
                <h3>${escapeHtml(coupon.title)}</h3>

                <div class="category-coupon-bottom">
                    <span>Hạn dùng: ${formatDate(coupon.expiry)}</span>

                    <a 
                        href="/app/views/user/coupon_detail.php?code=${encodeURIComponent(coupon.code)}" 
                        class="category-view-button"
                    >
                        Chi tiết
                    </a>
                </div>
            </article>
        `;
    });

    list.innerHTML = html;
}

function toggleFavorite(code, btnElement) {
    var favs = getFavorites();
    var index = favs.indexOf(code);

    if (index === -1) {
        favs.push(code);
        if (btnElement) {
            btnElement.classList.add('active');
            var textSpan = btnElement.querySelector('.fav-text');
            if (textSpan) textSpan.textContent = 'Đã lưu';
        }
    } else {
        favs.splice(index, 1);
        if (btnElement) {
            btnElement.classList.remove('active');
            var textSpan = btnElement.querySelector('.fav-text');
            if (textSpan) textSpan.textContent = 'Yêu thích';
        }
    }

    saveFavorites(favs);

    // Nếu đang ở trang Yêu thích, xóa bớt mã vừa bỏ tim
    var card = document.getElementById('coupon-card-' + code);
    if (card && document.getElementById('favoriteCouponList')) {
        card.style.transition = 'all 0.3s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(function () {
            card.remove();
            if (getFavorites().length === 0) {
                var emptyNotice = document.getElementById('emptyFavoriteNotice');
                if (emptyNotice) emptyNotice.style.display = 'block';
            }
        }, 300);
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('-');
    return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : dateStr;
}
/* ===== 3. MOBILE MENU ===== */

function initMobileMenu() {
    var toggleBtn = document.getElementById('mobileMenuToggle');
    var nav = document.getElementById('mainNav');

    if (toggleBtn && nav) {
        toggleBtn.addEventListener('click', function () {
            nav.classList.toggle('open');
            toggleBtn.classList.toggle('active');
        });
    }
}

/* ===== 4. MODAL ĐĂNG NHẬP / ĐĂNG KÝ ===== */

function initAuthModal() {
    var openBtns = document.querySelectorAll('.open-auth-modal');
    var modal = document.getElementById('authModal');
    var closeBtn = document.getElementById('closeAuthModal');
    var tabBtns = document.querySelectorAll('.auth-tab-btn');

    openBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (modal) modal.style.display = 'flex';
        });
    });

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            var target = btn.getAttribute('data-target');
            document.querySelectorAll('.auth-form-content').forEach(function (f) {
                f.style.display = 'none';
            });
            var targetForm = document.getElementById(target);
            if (targetForm) targetForm.style.display = 'block';
        });
    });
}

/* ===== 5. TÌM KIẾM TỨC THÌ (LIVE SEARCH HEADER) ===== */

function initLiveSearch() {
    var input = document.getElementById('headerSearchInput');
    var suggestionsBox = document.getElementById('searchSuggestions');
    var coupons = window.ALL_COUPONS || [];

    if (!input || !suggestionsBox) return;

    input.addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();

        if (query.length === 0) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.remove('active');
            return;
        }

        var matches = coupons.filter(function (item) {
            return item.store.toLowerCase().includes(query) ||
                   item.title.toLowerCase().includes(query) ||
                   item.code.toLowerCase().includes(query) ||
                   item.category.toLowerCase().includes(query) ||
                   item.discount.toLowerCase().includes(query);
        });

        if (matches.length === 0) {
            suggestionsBox.innerHTML = '<div class="suggestion-empty">Không tìm thấy mã nào phù hợp</div>';
            suggestionsBox.classList.add('active');
            return;
        }

        var html = '';
        matches.slice(0, 5).forEach(function (item) {
            html += `
                <a href="/app/views/user/coupon_detail.php?code=${encodeURIComponent(item.code)}" class="suggestion-item">
                    <div class="suggestion-info">
                        <span class="suggestion-store">${escapeHtml(item.store)} · <strong class="suggestion-code">${escapeHtml(item.code)}</strong></span>
                        <span class="suggestion-title">${escapeHtml(item.title)}</span>
                    </div>
                    <span class="suggestion-discount">${escapeHtml(item.discount)}</span>
                </a>
            `;
        });

        html += `
            <a href="/app/views/user/search.php?q=${encodeURIComponent(query)}" class="suggestion-more">
                Xem tất cả ${matches.length} kết quả cho "${escapeHtml(query)}" ↗
            </a>
        `;

        suggestionsBox.innerHTML = html;
        suggestionsBox.classList.add('active');
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.header-search')) {
            suggestionsBox.classList.remove('active');
        }
    });

    input.addEventListener('focus', function () {
        if (this.value.trim().length > 0) {
            suggestionsBox.classList.add('active');
        }
    });
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

/* ===== TÍNH NĂNG ĐĂNG BÌNH LUẬN DYNAMIC ===== */

function initComments() {
    var input = document.getElementById('commentInput');
    var sendBtn = document.getElementById('sendCommentBtn');
    var list = document.getElementById('commentsList');

    if (!input || !sendBtn || !list) return;

    function handleSendComment() {
        var text = input.value.trim();
        if (text === '') {
            input.focus();
            return;
        }

        // Tạo thẻ bình luận mới
        var newComment = document.createElement('div');
        newComment.className = 'comment-item';
        newComment.style.opacity = '0';
        newComment.style.transform = 'translateY(-10px)';
        newComment.style.transition = 'all 0.3s ease';

        newComment.innerHTML = `
            <div class="comment-header">
                <span class="comment-author">Bạn (Thành viên)</span>
                <span class="comment-time">Vừa xong</span>
            </div>
            <p class="comment-text">${escapeHtml(text)}</p>
        `;

        // Thêm bình luận mới vào đầu danh sách
        list.insertBefore(newComment, list.firstChild);

        // Hiệu ứng hiện mượt mà
        setTimeout(function () {
            newComment.style.opacity = '1';
            newComment.style.transform = 'translateY(0)';
        }, 10);

        // Làm sạch ô nhập liệu
        input.value = '';
    }

    // Sự kiện click nút Gửi
    sendBtn.addEventListener('click', handleSendComment);

    // Sự kiện gõ Enter trong ô nhập
    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSendComment();
        }
    });
}

/* ===== XỬ LÝ ĐÓNG GÓP MÃ & ĐỌC MÃ CỦA TÔI ===== */

function getMyCoupons() {
    var defaultCoupons = [
        {
            code: 'TIKIBOOK20',
            store: 'Tiki',
            discount: 'GIẢM 20K',
            submitted_at: '12/10/2026',
            status: 'PENDING',
            status_text: 'Chờ duyệt',
            reason: ''
        },
        {
            code: 'GRABFOOD30',
            store: 'GrabFood',
            discount: 'GIẢM 30%',
            submitted_at: '10/10/2026',
            status: 'APPROVED',
            status_text: 'Đã duyệt',
            reason: ''
        },
        {
            code: 'SHOPEE99K',
            store: 'Shopee',
            discount: 'GIẢM 99K',
            submitted_at: '05/10/2026',
            status: 'REJECTED',
            status_text: 'Bị từ chối',
            reason: 'Mã không tồn tại hoặc đã hết số lượng sử dụng tại thời điểm kiểm tra.'
        }
    ];

    var stored = localStorage.getItem('couponhub_my_submitted_coupons');
    if (!stored) {
        return defaultCoupons;
    }
    return JSON.parse(stored);
}

function saveMyCoupons(coupons) {
    localStorage.setItem('couponhub_my_submitted_coupons', JSON.stringify(coupons));
}

function initSubmitCouponForm() {
    var form = document.getElementById('submitCouponForm');
    var alertBox = document.getElementById('submitSuccessAlert');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var store = document.getElementById('store').value.trim();
        var code = document.getElementById('code').value.trim().toUpperCase();
        var discount = document.getElementById('discount').value.trim();

        var today = new Date();
        var dateStr = String(today.getDate()).padStart(2, '0') + '/' + 
                      String(today.getMonth() + 1).padStart(2, '0') + '/' + 
                      today.getFullYear();

        var newCoupon = {
            code: code,
            store: store,
            discount: discount,
            submitted_at: dateStr,
            status: 'PENDING',
            status_text: 'Chờ duyệt',
            reason: ''
        };

        var myCoupons = getMyCoupons();
        myCoupons.unshift(newCoupon); // Đưa mã mới lên đầu danh sách
        saveMyCoupons(myCoupons);

        form.reset();
        if (alertBox) {
            alertBox.style.display = 'block';
            window.scrollTo({ top: alertBox.offsetTop - 100, behavior: 'smooth' });
        }
    });
}

function initMyCoupons() {
    var listContainer = document.getElementById('myCouponsList');
    if (!listContainer) return;

    var myCoupons = getMyCoupons();

    if (myCoupons.length === 0) {
        listContainer.innerHTML = '<div class="category-empty"><h3>Bạn chưa đóng góp mã nào</h3><p>Hãy chia sẻ mã giảm giá bạn biết để hỗ trợ cộng đồng nhé!</p></div>';
        return;
    }

    var html = '';
    myCoupons.forEach(function (item) {
        var statusClass = item.status.toLowerCase();
        html += `
            <div class="my-coupon-card">
                <div class="my-coupon-header">
                    <div>
                        <strong style="font-size: 16px; color: var(--heading);">${escapeHtml(item.code)}</strong>
                        <span class="store-tag" style="font-size: 13px; color: var(--muted); font-weight: 600;">(${escapeHtml(item.store)} - ${escapeHtml(item.discount)})</span>
                    </div>
                    <span class="badge-status ${statusClass}">
                        ${escapeHtml(item.status_text)}
                    </span>
                </div>
                
                <div class="my-coupon-meta" style="margin-top: 8px; font-size: 12px; color: var(--muted);">
                    <span>Ngày gửi: ${escapeHtml(item.submitted_at)}</span>
                </div>

                ${item.status === 'REJECTED' && item.reason ? `
                    <div class="rejection-box">
                        <strong>⚠️ Lý do Admin từ chối:</strong>
                        <p style="margin-top: 4px;">${escapeHtml(item.reason)}</p>
                    </div>
                ` : ''}
            </div>
        `;
    });

    listContainer.innerHTML = html;
}