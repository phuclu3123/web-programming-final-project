# Public UI and brand campaigns — 5 November 2026

## Design decisions

The public site is for young urban shoppers: choose an experience, understand a coupon, use it with its conditions. Campaigns now lead with the collection itself instead of a large abstract slogan and repeated countdowns.

- Main campaign: a distinct brand colour, real imagery, short description, actual coupon count, end date and one destination.
- Other brands: a manually controlled gallery with native horizontal scrolling, keyboard access and visible next/previous controls. This avoids eight long banners before the coupon list on a phone.
- Brand treatments: Nike uses sports photography and ink; Hasaki uses skincare packshots on a pale surface; Highlands uses its own coffee artwork; Phúc Long uses its official identity in a tea-green composition; CGV uses a clearly labelled cinema illustration. Shopee, Grab, Coolmate and Lazada retain their own visual identities.
- Campaign details: distinct participating brands, categories, minimum order, dates, usable coupon conditions and practical questions. These summaries come from publicly visible MySQL coupons, independent of the active coupon filter. Missing data stays empty, never a made-up number.
- Public community: a light reading area beside imagery, usable search and a single share action. The old dark overlay was removed from both pseudo-elements.
- Home: existing automatic hero retained, real Nike/Shopee imagery added, campaign previews reduced to two collections. Vietnamese-compatible system typography and existing Bootstrap controls retained.
- Admin: task header, workspace navigation and operational content; no public navigation, shopping footer or public-preview action. `/dashboard` redirects an administrator to `/admin`.
- Merchant: separate work header with a small public-preview link. The link remains in the account menu on mobile.

No new framework, animation library, authentication mechanism or database schema was introduced. HTML, PHP, CSS, JavaScript and Bootstrap remain the implementation stack.

## Skills and judgment

Applied `ui-ux-pro-max`, `design-taste-frontend` and `design-motion-principles`. Taste draw: `page-is-the-object`, seed `688487224`, retaining the site's palette and Vietnamese-compatible type. The collection/offer supplies the hierarchy; the suggested theatrical scroll scenes, testimonial pattern and purple palette were not applicable.

Motion is limited to manual spatial scrolling. Keyboard activation and reduced-motion preferences use an immediate scroll. No decorative entrances were added. Existing user-approved glass hero controls remain.

## Images

Local WebP assets and original URLs, dimensions and hashes are recorded in `assets/images/brands/sources.json`. Logo sources are in `assets/images/editorial/sources.json`.

- Shopee: Sea's official product introduction, `https://www.sea.com/products/shopee`.
- Nike: official Vietnam home page, `https://www.nike.com/vn/`.
- Coolmate: official collection home page, `https://www.coolmate.me/`.
- Grab: official press library, `https://www.grab.com/vn/pressdownload/`.
- Highlands: official website, `https://www.highlandscoffee.com.vn/`.
- Hasaki: product images published on `https://hasaki.vn/campaign/khai-truong-chi-nhanh-23`. The 300px source packshots are kept small and contained rather than stretched into a full-width photograph.
- Phúc Long: official website identity. Its dynamic website did not provide downloadable photography; the design uses its actual logo, not another cafe's photograph.
- Lazada: official blog, `https://www.lazada.vn/blog/thang-11-sale-lon-nhat-nam-lazada/`. The artwork is labelled as **11.11/2025 archive imagery**, separate from sample coupon values.
- CGV: the existing licensed Unsplash cinema image is explicitly labelled as illustration. It is not presented as an actual CGV venue photograph.

Attribution is visible beside the new campaign media. These are reference assets for the educational local showcase; no licence or commercial partnership is inferred from their public availability.

## Persistent data, separate from UI rendering

`scripts/seed-ui-showcase.php` is an explicit CLI-only, additive, transactional import. Public PHP views read MySQL repositories; they do not render an in-memory fixture. The import requires the existing demo administrator and merchant accounts, checks merchant status and management, and uses an advisory lock. It reuses approved merchants by slug **or name**, and records stable campaign slugs and coupon codes to avoid duplicates.

Local `dbcouponshubweb_v2` was backed up before insertion. The operation added 2 merchants (Nike, Phúc Long), 8 campaigns, 8 coupons and 19 links. Totals after insertion: **14 merchants, 10 campaigns, 40 coupons**. Every pre-existing row in users, merchants, coupons, campaigns, memberships and campaign links was checked byte-for-byte unchanged. A second execution added zero rows or links.

The backup is outside the web root at `%LOCALAPPDATA%\CouponHub\backups\dbcouponshubweb_v2-before-ui-showcase-20261105-091551.sql`. The import receipt is `.runtime/live-showcase-receipt.json`. They are not committed. The VS Code preview server serves arbitrary static files from the workspace, so SQL backups must remain outside it; the temporary in-workspace backup was moved and its hash verified. No schema/demo re-import or password reset occurred. Sample promotions remain labelled `[DEMO]`; values and conditions are illustrative, not verified live brand offers. Dates are stored at import, not regenerated on each request. Sample campaigns expire after 90 days, sample coupons after 75 days.

To use the additive dataset on another existing local demo database, back it up first, configure the usual database environment and run:

```powershell
C:\xampp\php\php.exe scripts/seed-ui-showcase.php --apply --expected-database=YOUR_LOCAL_DB
```

Do not replace an existing database with `schema.sql` or `demo.sql` for this UI update.

## Verification

- 90 existing JavaScript tests passed.
- 7 focused PHP presentation checks passed: distinct summaries, zero versus empty minimums, brand art, custom-banner precedence, invalid-banner fallback, HTML escaping, demo disclosure and subfolder links.
- Root and `/CouponHub_UI` HTTP acceptance passed, including all 36 admin routes per base, role/CSRF rejection, sessions/cookies and existing coupon interaction handling.
- Chrome acceptance passed **164 page/viewport checks** at 320, 390, 768 and 1440px. It now explicitly verifies a non-empty campaign layout, actual gallery scrolling, no admin shopping chrome, merchant preview access and removal of the obsolete community overlay.
- Screenshots were inspected and corrected after the first pass: community overlay, dark-surface disclosure contrast, image credits, Hasaki product background and mobile card width.
- Taste inspection records are local under `.runtime/ui-refresh-*`. Its stylistic objections to brand orange, approved floating control shadows and public-page heading size are documented exceptions, not a claim of zero findings or an accessibility certification. Actual low-contrast new text was corrected.

Live local entry: `http://localhost:8000/CouponHub_UI/`; campaigns: `http://localhost:8000/CouponHub_UI/campaigns`.

## Browse-layout follow-up — 2026-11-05

The campaign directory no longer opens with phase tabs and a large filter panel. Its coupon preview uses short brand names, non-wrapping counts and a scrollbar-free selection rail; the duplicated coupon filter form is removed. Detailed campaign pages keep their existing filter IDs, GET parameters, AJAX and reset behavior inside optional native `details` controls. Legacy filtered directory URLs retain a visible reset link.

The public directory requests up to 24 campaigns per page instead of 9, within the repository's existing limit. The current 10-campaign collection stays together; the old `?page=2` URL is clamped to the same complete collection. Larger collections retain bounded pagination. Gallery controls sit on opposite sides of the rail with native scrolling and keyboard/reduced-motion behavior preserved.

Home removes the three shortcuts after the hero and the repeated search instructions. A heading/search row leads into compact text categories, an optional filter control and the results. Category measurements retain intrinsic label widths so small screens disclose overflow through the existing category dialog. Community changes are limited to the search action's pill geometry. The journal's shelf is now in normal cover flow; its final estimate link is framed by decorative local brand logos and SVG geometry, with a separate mobile arrangement. These logos are illustrative decoration, not a claim of partnership.

Follow-up acceptance, separate from the earlier full application audit:

- All 24 public page/viewport checks passed at 320, 390, 768 and 1440px on the existing local PHP/MySQL preview: home, campaigns, legacy campaign page 2, community, journal and campaign detail.
- Verified actual gallery scrolling and left/right button placement, hidden rail scrollbars, visible category label widths, search button containment, empty search results, category-dialog selection, campaign preview selection, detailed coupon filtering and reset. No uncaught JavaScript exceptions occurred during these checks.
- Four campaign JavaScript regression tests and seven PHP presentation checks passed. Changed PHP templates/controller passed syntax checks, and `git diff --check` passed.
- Final focused browser evidence: `.runtime/browse-review-1793856558735591300/`. Design diagnostics and full-page screenshots: `.runtime/browse-final-{home,campaign,articles}/`. The style meter still objects to existing glass-control shadows, demo-code labels, existing home sections and large editorial titles. Those findings are not represented as a zero-finding or full accessibility certification. Newly touched prose contrast was improved; decorative geometry follows the explicit design request.

No database writes, schema imports, account changes or authentication/API changes were part of this follow-up. Existing user-owned local services were kept running.

### Subsequent journal simplification and right-side contact actions

The journal now opens with one short feature and two compact reading links. The old shelf, paragraphs, duplicated guide card and topic-control row were removed from this landing layout; complete guide content and existing topic URLs remain available. Published database articles still render when present. The final brand composition keeps one short headline and its estimate link.

Floating contact actions are anchored at the lower right. Initially the contact button occupies the rightmost position. After about 240px of scrolling it slides left by one return-button width plus the gap (58px desktop / 54px mobile), while the top action appears to its right using transform/opacity transitions (300ms/240ms). The state reverses on return; reduced motion is immediate, keyboard activation scrolls immediately, and the contact panel opens from the right within the viewport. The existing inert state, Escape close and focus return remain.

All 24 public page/viewport checks passed again after these changes, including popup bounds at 320–1440px, actual contact displacement, back-to-top visibility, Escape/focus and reduced motion. All three legacy topic URLs and their guide destinations returned successfully. Eight targeted JavaScript regressions passed; PHP syntax and whitespace checks passed. Final focused evidence is `.runtime/browse-review-1793858073760565200/`; journal screenshots are `.runtime/journal-short-final/`.

The subsequent right-anchor correction passed focused browser checks at 320, 390, 768 and 1440px: initial right-edge position, 54/58px left displacement, adjacent return-button alignment, popup bounds, Escape focus return and reduced motion. Evidence: `.runtime/browse-review-1793859955604286000/`. Four existing floating-action regression tests passed.
