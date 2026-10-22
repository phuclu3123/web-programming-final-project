// Đăng nhập + đăng ký gửi POST tới API PHP; quên mật khẩu vẫn thử giao diện.
// Luồng đọc code: lấy phần tử → kiểm tra dữ liệu → submit/fetch → tương tác popup.
(() => {
    // Tìm popup trong HTML đã được PHP ghép; trang không có popup thì dừng.
    const modal = document.getElementById("loginModal");
    if (!modal) return;

    // forms: ba biểu mẫu để bắt submit, kiểm tra input và gom dữ liệu gửi.
    const forms = [...modal.querySelectorAll("form")];
    // account: một input để đặt con trỏ khi mở popup, không phải mảng.
    const account = modal.querySelector("#loginAccount");
    // views: ba div bọc cả tiêu đề + form + nút để chuyển màn hình trong popup.
    const views = [...modal.querySelectorAll("[data-auth-view]")];
    // Các nút mắt; data-password-toggle chứa ID của ô mật khẩu được điều khiển.
    const passwordButtons = [
        ...modal.querySelectorAll("[data-password-toggle]"),
    ];

    // ---- PHẦN DÙNG CHUNG (ĐÃ CÓ): thông báo và kiểm tra form ----
    /**
     * Dọn thông báo và dấu lỗi cũ của một form, không xóa dữ liệu đã nhập.
     * .auth-message là vùng chứa chữ; aria-invalid đánh dấu input bị lỗi.
     */
    function clearFeedback(form) {
        const message = form.querySelector(".auth-message");
        if (message) {
            message.hidden = true;
            message.textContent = "";
            delete message.dataset.state;
        }
        form.querySelectorAll("input").forEach((input) => {
            input.removeAttribute("aria-invalid");
        });
    }

    /** Hiện thông báo trong form; state là "error" hoặc "success" để CSS chọn màu. */
    function showMessage(form, text, state) {
        const message = form.querySelector(".auth-message");
        if (!message) return;
        message.textContent = text;
        message.dataset.state = state;
        message.hidden = false;
        message.scrollIntoView({ block: "nearest" });
    }

    /**
     * Kiểm tra ô trống, email, điện thoại và mật khẩu đăng ký.
     * Trả mảng lỗi [{ input, text }]; [] nghĩa là không có lỗi phía giao diện.
     * Hàm không gọi API; PHP vẫn phải kiểm tra dữ liệu nhận được ở server.
     */
    function validateForm(form) {
        const errors = [];
        // Đánh dấu ô sai và nhớ cả ô + lời báo để focus vào lỗi đầu tiên sau đó.
        const addError = (input, text) => {
            input.setAttribute("aria-invalid", "true");
            errors.push({ input, text });
        };

        form.querySelectorAll("input").forEach((input) => {
            // Token CSRF/return_url do PHP tạo, không phải ô người dùng cần kiểm tra.
            if (input.type === "hidden") return;
            const label = form.querySelector(`label[for="${input.id}"]`);
            const name = label?.textContent.trim() || "Thông tin";
            // Không trim mật khẩu vì khoảng trắng có thể là một phần mật khẩu.
            const isPassword = input.autocomplete.includes("password");
            const value = isPassword ? input.value : input.value.trim();
            if (!isPassword) input.value = value;

            if (input.required && !value) {
                addError(input, `Vui lòng nhập ${name.toLowerCase()}.`);
            } else if (
                input.maxLength > 0 &&
                [...value].length > input.maxLength
            ) {
                addError(input, `${name} tối đa ${input.maxLength} ký tự.`);
            } else if (input.type === "email" && input.validity.typeMismatch) {
                addError(input, "Địa chỉ email chưa đúng định dạng.");
            } else if (
                input.type === "tel" &&
                value &&
                (!/^\+?[0-9][0-9 ()-]*$/.test(value) ||
                    value.length > 20 ||
                    value.replace(/\D/g, "").length < 8 ||
                    value.replace(/\D/g, "").length > 15)
            ) {
                addError(
                    input,
                    "Số điện thoại cần có 8–15 chữ số, tối đa 20 ký tự.",
                );
            }
        });

        if (form.id === "registerForm") {
            // ---- PHẦN ĐĂNG KÝ (BỔ SUNG): username phải khớp quy tắc ở PHP ----
            const username = form.querySelector("#registerUsername");
            if (
                username.value &&
                !/^[A-Za-z0-9_.-]{1,50}$/.test(username.value)
            ) {
                addError(
                    username,
                    "Tên tài khoản chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.",
                );
            }
            const password = form.querySelector("#registerPassword");
            const confirmation = form.querySelector("#registerPasswordConfirm");
            const value = password.value;
            if (value) {
                const missing = [];
                if ([...value].length < 8) missing.push("ít nhất 8 ký tự");
                if (new TextEncoder().encode(value).length > 72)
                    missing.push("tối đa 72 byte");
                if (value.includes("\0")) missing.push("không chứa ký tự null");
                if (!/\p{Lu}/u.test(value)) missing.push("chữ hoa");
                if (!/\p{Ll}/u.test(value)) missing.push("chữ thường");
                if (!/[0-9]/.test(value)) missing.push("số");
                if (!/[^\p{L}\p{N}\s]/u.test(value))
                    missing.push("ký tự đặc biệt");
                if (missing.length)
                    addError(
                        password,
                        `Mật khẩu cần có ${missing.join(", ")}.`,
                    );
            }
            if (confirmation.value && confirmation.value !== value) {
                addError(confirmation, "Mật khẩu nhập lại chưa khớp.");
            }
        }
        return errors;
    }

    // ---- PHẦN QUÊN MẬT KHẨU: vẫn thử UI, chưa gửi OTP ----
    const previewMessages = {
        forgotPasswordForm:
            "Email hợp lệ về định dạng. Đây là bản thử giao diện, chưa gửi email hoặc mã OTP.",
    };

    // ---- PHẦN ĐĂNG KÝ (BỔ SUNG): gửi dữ liệu và chuyển về đăng nhập ----
    async function submitRegistration(form) {
        const button = [...form.elements].find(
            (element) => element.type === "submit",
        );
        if (button?.disabled) return;
        const originalText = button?.textContent;
        // Nhớ username trước khi reset. Chỉ điền account, không chép mật khẩu sang login.
        const username = form.querySelector("#registerUsername").value;
        try {
            if (button) {
                button.disabled = true;
                button.textContent = "Đang tạo tài khoản...";
            }
            const response = await fetch(form.action || "api/register.php", {
                method: "POST",
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok || result.success !== true) {
                // errors của PHP dùng tên trường (name), không dùng id của HTML.
                let firstInvalid;
                if (result.errors && typeof result.errors === "object") {
                    form.querySelectorAll("input").forEach((input) => {
                        if (Object.hasOwn(result.errors, input.name)) {
                            input.setAttribute("aria-invalid", "true");
                            firstInvalid ??= input;
                        }
                    });
                }
                showMessage(
                    form,
                    result.message || "Không thể tạo tài khoản.",
                    "error",
                );
                firstInvalid?.focus({ preventScroll: true });
                return;
            }

            form.reset();
            // Chỉ chuyển màn hình nếu người dùng vẫn đang xem đăng ký.
            // Nếu họ đã đóng/chuyển popup lúc chờ, không tự giành lại focus.
            const registerView = views.find(
                (view) => view.dataset.authView === "register",
            );
            const loginForm = modal.querySelector("#loginForm");
            if (
                modal.classList.contains("show") &&
                registerView &&
                !registerView.hidden
            ) {
                showView("login", false);
                if (account) account.value = username;
                const loginPassword = modal.querySelector("#loginPassword");
                if (loginPassword) loginPassword.value = "";
                showMessage(
                    loginForm,
                    result.message ||
                        "Tạo tài khoản thành công. Hãy đăng nhập.",
                    "success",
                );
                loginPassword?.focus({ preventScroll: true });
            }
        } catch (error) {
            showMessage(
                form,
                "Không thể nhận kết quả đăng ký. Vui lòng thử lại.",
                "error",
            );
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    }

    // ---- PHẦN SUBMIT: dùng chung kiểm tra, rồi tách từng chức năng ----
    // Gắn handler cho từng form. Submit form nào thì chỉ xử lý form đó.
    forms.forEach((form) => {
        form.addEventListener("submit", async (event) => {
            // Chặn gửi form theo cách mặc định; tự kiểm tra rồi gửi bằng fetch.
            event.preventDefault();
            clearFeedback(form);
            const errors = validateForm(form);
            if (errors.length) {
                showMessage(
                    form,
                    errors.map((error) => error.text).join(" "),
                    "error",
                );
                errors[0].input.focus({ preventScroll: true });
                return;
            }
            // ---- PHẦN ĐĂNG KÝ (BỔ SUNG) ----
            if (form.id === "registerForm") {
                await submitRegistration(form);
                return;
            }
            // ---- PHẦN QUÊN MẬT KHẨU: giữ thông báo thử ----
            if (form.id === "forgotPasswordForm") {
                showMessage(form, previewMessages[form.id], "success");
                return;
            }

            // ---- PHẦN ĐĂNG NHẬP (ĐÃ CÓ): giữ fetch và tải lại trang ----
            // Nút submit nằm ngoài form nên dùng form.elements.
            const submitButton = [...form.elements].find(
                (element) => element.type === "submit",
            );

            if (submitButton?.disabled) return;

            const originalText = submitButton?.textContent;

            try {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = "Đang đăng nhập...";
                }

                const response = await fetch(form.action || "api/login.php", {
                    method: "POST",
                    // Lấy giá trị theo name="account"/"password", không theo id.
                    body: new FormData(form),
                });

                // response là phản hồi HTTP; result là dữ liệu JSON PHP trả về.
                const result = await response.json();

                if (!response.ok || result.success !== true) {
                    showMessage(
                        form,
                        result.message || "Không thể đăng nhập.",
                        "error",
                    );
                    return;
                }

                // ---- REDIRECT (BỔ SUNG): PHP chọn trang theo role/return_url ----
                const destination = new URL(
                    result.redirect || "index.php",
                    document.baseURI || window.location.href,
                );
                if (destination.origin !== window.location.origin) {
                    throw new Error("Invalid redirect origin");
                }
                window.location.assign(destination.href);
            } catch (error) {
                // Mất mạng hoặc không đọc được JSON thì báo lỗi trong popup.
                showMessage(
                    form,
                    "Không thể nhận kết quả đăng nhập. Vui lòng thử lại.",
                    "error",
                );
            } finally {
                // Luôn mở lại nút và trả chữ ban đầu khi xử lý đã kết thúc.
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }
        });
        // Người dùng sửa dữ liệu → bỏ thông báo cũ; submit tiếp sẽ kiểm tra lại.
        form.addEventListener("input", () => clearFeedback(form));
    });

    /** Đổi text/password và icon mắt cho một ô; giữ nguyên giá trị mật khẩu. */
    function setPasswordVisibility(button, show) {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input || !modal.contains(input)) return;

        input.type = show ? "text" : "password";
        button.setAttribute("aria-pressed", String(show));
        const suffix =
            input.id === "registerPasswordConfirm" ? " nhập lại" : "";
        button.setAttribute(
            "aria-label",
            (show ? "Ẩn mật khẩu" : "Hiện mật khẩu") + suffix,
        );

        const icon = button.querySelector("i");
        icon?.classList.toggle("bi-eye", !show);
        icon?.classList.toggle("bi-eye-slash", show);
    }

    /** Che lại tất cả mật khẩu khi chuyển form hoặc đóng popup. */
    function hidePasswords() {
        passwordButtons.forEach((button) =>
            setPasswordVisibility(button, false),
        );
    }

    // Gắn click cho mỗi nút mắt, không submit form khi bấm nút này.
    passwordButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const input = document.getElementById(
                button.dataset.passwordToggle,
            );
            if (input) setPasswordVisibility(button, input.type === "password");
        });
    });

    /**
     * Chọn một vùng login/register/forgot-password để hiện, ẩn hai vùng còn lại.
     * Dọn thông báo, che mật khẩu, cập nhật tiêu đề cho hỗ trợ truy cập.
     * focus=false khi đóng popup để không đưa con trỏ vào vùng đang ẩn.
     */
    function showView(name, focus = true) {
        const activeView = views.find((view) => view.dataset.authView === name);
        if (!activeView) return;
        hidePasswords();
        forms.forEach(clearFeedback);
        views.forEach((view) => {
            view.hidden = view.dataset.authView !== name;
        });
        // Liên kết dialog với tiêu đề HTML của form đang hiện.
        const title = activeView.querySelector(".modal-title");
        if (title) modal.setAttribute("aria-labelledby", title.id);
        if (focus) {
            activeView
                ?.querySelector(
                    'input:not([type="hidden"]), [data-auth-focus], button',
                )
                ?.focus();
        }
    }

    // Bắt nút chuyển form qua data-auth-target; closest tìm cả khi bấm vào icon.
    modal.addEventListener("click", (event) => {
        const button = event.target.closest("[data-auth-target]");
        if (button) showView(button.dataset.authTarget);
    });

    // Sự kiện Bootstrap: mở popup xong → đặt con trỏ vào ô tài khoản.
    modal.addEventListener("shown.bs.modal", () => {
        account?.focus();
    });

    // Sự kiện Bootstrap: đóng xong → reset dữ liệu, xóa lỗi, trở về đăng nhập.
    modal.addEventListener("hidden.bs.modal", () => {
        forms.forEach((form) => {
            form.reset();
            clearFeedback(form);
        });
        showView("login", false);
    });
    // Trang Auth hiển thị trực tiếp biểu mẫu, không có màn hình chào/popup phía sau.
    if (modal.dataset?.authPage) showView(modal.dataset.authPage, false);
    if (typeof modal.showModal === 'function') {
        let trigger;
        const openAuth = (name = 'login', returnUrl) => {
            trigger = document.activeElement;
            const returnField = modal.querySelector('[name="return_url"]');
            if (returnField && returnUrl !== undefined) returnField.value = returnUrl;
            showView(name, false);
            if (!modal.open) modal.showModal();
            document.documentElement.classList.add('auth-dialog-open');
            modal.querySelector(`[data-auth-view="${name}"] input:not([type="hidden"])`)?.focus();
        };
        document.addEventListener('click', event => {
            const link = event.target.closest('[data-auth-open], a[href]');
            if (!link || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
            let target;
            try { target = new URL(link.href || '', document.baseURI); } catch { return; }
            const authUrl = /\/(login(?:\.php)?|register)\/?$/.test(target.pathname);
            if (!link.dataset.authOpen && (!authUrl || target.origin !== window.location.origin)) return;
            event.preventDefault();
            openAuth(link.dataset.authOpen || (target.pathname.endsWith('/register') ? 'register' : 'login'), target.searchParams.get('return') || '');
        });
        modal.querySelector('[data-auth-close]')?.addEventListener('click', () => modal.close());
        modal.addEventListener('click', event => {
            const rect = modal.getBoundingClientRect();
            if (event.target === modal && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) modal.close();
        });
        modal.addEventListener('close', () => {
            document.documentElement.classList.remove('auth-dialog-open');
            forms.forEach(form => { form.reset(); clearFeedback(form); });
            showView('login', false);
            trigger?.focus();
        });
        if (modal.dataset.authAuto) openAuth(modal.dataset.authAuto);
    }
})();
