const $ = jQuery;

export default class AddressPageController {
  private selector = '.entity-type-control input';
  private locale: Record<string, string>;

  public init(): void {
    this.locale = JSON.parse(window.wc_address_i18n_params.locale_fields);
  }

  public finalize(): void {
    $(document.body).on('change refresh', this.selector, (e) => {
      console.log('change refresh');
      this.toggleEntityType($(e.target));
    });

    $(document.body).on('country_to_state_changing', () => {
      window.setTimeout(
        () => this.toggleEntityType($(`${this.selector}:checked`)),
        100,
      );
      console.log('country_to_state_changing');
    });
  }

  private toggleEntityType($input: JQuery<HTMLInputElement>): void {
    const isCompany = $input.val() === 'company';

    console.log($input.is(':checked'), $input.val());

    this.isRequired($<HTMLParagraphElement>('.entity-type-toggle'), isCompany);
  }

  private isRequired(
    $field: JQuery<HTMLParagraphElement>,
    required: boolean,
  ): void {
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

  private toggleFields($toggle: JQuery<HTMLInputElement>): void {
    const isPerson = $toggle.attr('value') === 'person';

    $('.hide-if-person').toggleClass('shown', !isPerson);
  }
}
