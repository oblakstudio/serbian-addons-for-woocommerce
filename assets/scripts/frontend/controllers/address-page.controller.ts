const $ = jQuery;

export default class AddressPageController {
  private $inputs: JQuery<HTMLInputElement>;

  public init(): void {
    this.$inputs = $('.entity-type-control input[type="radio"]');
  }

  public finalize(): void {
    this.$inputs.on('click', ({ currentTarget }) =>
      this.toggleFields($(currentTarget)),
    );
    this.toggleFields(this.$inputs.filter(':checked'));
  }

  private toggleFields($toggle: JQuery<HTMLInputElement>): void {
    const isPerson = $toggle.attr('value') === 'person';

    $('.hide-if-person').toggleClass('shown', !isPerson).find('input').prop({
      required: !isPerson,
      disabled: isPerson,
    });
  }
}
