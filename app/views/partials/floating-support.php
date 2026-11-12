<?php /* Giữ nhóm liên hệ và về đầu trang dùng chung; JavaScript chỉ điều khiển vị trí hiển thị. */ ?>  <aside class="floating-support" data-floating-support aria-label="Liên hệ và điều hướng nhanh">
    <section class="contact-popover" id="contact-popover" role="dialog" aria-modal="false"
      aria-labelledby="contact-title" aria-hidden="true" inert>
      <div class="contact-popover-head">
        <div>
          <span class="contact-availability"><i aria-hidden="true"></i> Hỗ trợ trực tuyến</span>
          <strong id="contact-title">Bạn cần hỗ trợ?</strong>
        </div>
        <button class="contact-close" type="button" data-contact-close aria-label="Đóng bảng liên hệ">×</button>
      </div>
      <div class="contact-options">
        <a class="contact-option" href="https://zalo.me/" target="_blank" rel="noopener noreferrer">
          <span class="contact-channel-icon contact-channel-zalo" aria-hidden="true">Z</span>
          <span><strong>Zalo</strong><small>Trò chuyện nhanh</small></span>
          <?= icon('arrow') ?>
        </a>
        <a class="contact-option" href="https://www.messenger.com/" target="_blank" rel="noopener noreferrer">
          <span class="contact-channel-icon contact-channel-messenger" aria-hidden="true">M</span>
          <span><strong>Messenger</strong><small>Nhắn tin qua Facebook</small></span>
          <?= icon('arrow') ?>
        </a>
        <a class="contact-option" href="mailto:hello@couponhub.vn?subject=Hỗ trợ từ CouponHub">
          <span class="contact-channel-icon contact-channel-mail"
            aria-hidden="true"><?= icon('mail') ?></span>
          <span><strong>Email</strong><small>hello@couponhub.vn</small></span>
          <?= icon('arrow') ?>
        </a>
      </div>
      <p class="contact-note">Phản hồi trong giờ làm việc · 08:00–18:00</p>
    </section>
    <div class="floating-support-actions">
      <button class="glass-action contact-trigger" type="button" data-contact-trigger aria-expanded="false"
        aria-controls="contact-popover">
        <?= icon('message') ?><span>Liên hệ</span>
      </button>
      <button class="glass-action back-to-top" type="button" data-back-to-top tabindex="-1" aria-hidden="true" aria-label="Về đầu trang"
        title="Về đầu trang">
        <?= icon('arrow-up') ?>
      </button>
    </div>
  </aside>
