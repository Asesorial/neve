const slugify = require('url-slug');
describe('Visual Regression Testing - https://qa-neve.themeisle.com/neve-child-theme/2019/01/07/beatum-inquit/', () => {
	let url = "https://qa-neve.themeisle.com/neve-child-theme/2019/01/07/beatum-inquit/";

	let maskElement = '.pikaday__display--pikaday, .elementor-widget-video, .products img.attachment-woocommerce_thumbnail, label[for="vscf_captcha"],.elementor-widget-google_maps,.pikaday__display,.elementor-video-iframe';
	let clipElement = '.wpcf7-quiz-label, .eaw-typed-text,.product .related.products,.captcha_img,.elementor-element-38f7ff1e,.elementor-widget-container[style*="will-change"], .particles-js-canvas-el,.product_list_widget li + li, .exclusive.products';

	it('Should not add any visual change', function () {
		cy.visit(url);
		cy.maskAndClip(maskElement, clipElement);
		cy.captureDocument(slugify(url));
	});
});
