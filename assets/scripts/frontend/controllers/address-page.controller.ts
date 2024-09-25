const $ = jQuery;

export default class AddressPageController {
  private selector = '.entity-type-control input';
  private locale: Record<string, string>;

  public init(): void {
    this.locale = JSON.parse(window.wc_address_i18n_params.locale_fields);
  }

  public finalize(): void {
    $(document.body).on('change refresh', this.selector, (e) => {
      this.toggleEntityType($(e.target));
    });

    $(document.body).on('country_to_state_changing', () => {
      window.setTimeout(
        () => this.toggleEntityType($(`${this.selector}:checked`)),
        100,
      );
    });
  }

  private toggleEntityType($field: JQuery<HTMLInputElement>): void {
    const required = $field.val() === 'company';

    $field.find('input').prop({
      'aria-required': required,
      disabled: !required,
    });

    if (required) {
      $field.find('label .optional').remove();
      $field.addClass('shown validate-required');

      if ($field.find('label .required').length === 0) {
        $field
          .find('label')
          .append(
            `<abbr class="required" title="${window.wc_address_i18n_params.i18n_required_text}">*</abbr>`,
          );
      }

      return;
    }

    $field.find('label .required').remove();
    $field.removeClass(
      'shown validate-required woocommerce-invalid woocommerce-invalid-required-field',
    );

    if ($field.find('label .optional').length === 0) {
      $field
        .find('label')
        .append(
          `<abbr class="optional" title="${window.wc_address_i18n_params.i18n_optional_text}">${window.wc_address_i18n_params.i18n_optional_text}</abbr>`,
        );
    }
  }
}
