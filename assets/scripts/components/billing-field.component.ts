export class BillingField {
  private typeControl: NodeListOf<HTMLInputElement>;

  constructor() {
    this.typeControl = document.querySelectorAll('.entity-type-control input');
  }

  run(): void {
    this.bindEvents();
    this.toggleCompanyFields();
  }

  private bindEvents(): void {
    if (this.typeControl[0].type != 'radio') {
      console.log('here');
      return;
    }

    this.typeControl.forEach((control) => control.addEventListener('click', () => this.toggleCompanyFields()));
  }

  private toggleCompanyFields(): void {
    const type =
      this.typeControl[0].type == 'radio' ? [...this.typeControl].filter((control) => control.checked)[0].value : this.typeControl[0].value;

    document.querySelectorAll<HTMLDivElement>('.hide-if-person').forEach((holder) => {
      const input = holder.querySelector<HTMLInputElement>('input');

      holder.classList.toggle('shown', type != 'person');
      input.required = type == 'person';
      input.disabled = type == 'person';
    });
  }
}
