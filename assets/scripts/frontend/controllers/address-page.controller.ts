const $ = jQuery;

export default class AddressPageController {
  private labels: Record<string, string>;

  public init(): void {
    this.labels = {
      reqText: window.wc_address_i18n_params.i18n_required_text,
      optText: window.wc_address_i18n_params.i18n_optional_text,
    };

    const $ctrl = $('.entity-type-control input');

    if ($ctrl.data('default')) {
      $ctrl.val($ctrl.data('default'));
    }
  }

  public finalize(): void {
    $(document.body).on('change refresh', '.entity-type-control input', ({ target }) => {
      this.toggleFields($(target));
    });

    $(document.body).on('country_to_state_changing', () => {
      const selector = $('.entity-type-control input:checked').length
        ? '.entity-type-control input:checked'
        : '.entity-type-control input';

      window.setTimeout(() => this.toggleFields($(selector)), 100);
    });
  }

  private toggleFields($input: JQuery<HTMLInputElement>): void {
    const required = $input.val() === 'company';
    const $field = $<HTMLParagraphElement>('.entity-type-toggle');

    $field.find('input').prop({
      'aria-required': required,
      disabled: !required,
    });

    if (required) {
      $field.find('label .optional').remove();
      $field.addClass('shown validate-required');

      if ($field.find('label .required').length === 0) {
        $field.find('label').append(`<abbr class="required" title="${this.labels.reqText}">*</abbr>`);
      }

      return;
    }

    $field.find('label .required').remove();
    $field.removeClass('shown validate-required woocommerce-invalid woocommerce-invalid-required-field');

    if ($field.find('label .optional').length === 0) {
      $field
        .find('label')
        .append(`<abbr class="optional" title="${this.labels.optText}">${this.labels.optText}</abbr>`);
    }
  }
}
