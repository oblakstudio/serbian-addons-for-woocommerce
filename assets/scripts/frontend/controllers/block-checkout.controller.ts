const $ = jQuery;

export class BlockCheckoutController {
  init(): void {
    $(document.body).on('change', '.wc-block-components-select-input-wcsrb-type select', (e) =>
      this.toggleFields($(e.target).val() === 'company'),
    );
  }

  finalize(): void {
    $(document.body).on('click', '.wc-block-components-address-card__edit', (e) => {
      window.setTimeout(() => {
        $('.wc-block-components-select-input-wcsrb-type select').trigger('change');
      }, 100);
    });
  }

  toggleFields(isCompany: boolean): void {
    const $fields = $('input[data-shown-type="company"], .wc-block-components-address-form__company input');

    console.log('toggleFields', isCompany, $fields);
    if (isCompany) {
      $fields.parent('.wc-block-components-text-input').show().addClass('is-active');

      return;
    }

    $fields.val('').trigger('change').parent('.wc-block-components-text-input').hide().removeClass('is-active');
  }
}
