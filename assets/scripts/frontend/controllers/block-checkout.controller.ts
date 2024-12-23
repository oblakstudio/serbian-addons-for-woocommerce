const $ = jQuery;

export class BlockCheckoutController {
  init(): void {
    $(document.body).on('change', '.wc-block-components-select-input-wcsrb-type select', (e) =>
      this.toggleFields($(e.target).val() === 'company'),
    );
  }

  finalize(): void {
    console.log('BlockCheckoutController.finalize');
  }

  toggleFields(isCompany: boolean): void {
    const $fields = $('input[data-shown-type="company"], .wc-block-components-address-form__company input');

    if (isCompany) {
      $fields.parent('.wc-block-components-text-input').show().addClass('is-active');

      return;
    }

    // $fields.each(function () {
    //   $(this).val(''); //.parent('.wc-block-components-text-input').hide();
    //   console.log('toggleFields', $(this).val());
    // });

    $fields.val('').trigger('change').parent('.wc-block-components-text-input').hide().removeClass('is-active');
  }
}
