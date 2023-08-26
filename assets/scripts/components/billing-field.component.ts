export class BillingField {
  private typeControl: NodeListOf<HTMLInputElement>;
  private active = true;

  constructor() {
    this.typeControl = document.querySelectorAll('.entity-type-control input');
  }

  run(): void {
    this.bindEvents();
    this.toggleCompanyFields();
  }

  private bindEvents(): void {
    if (this.typeControl.length === 0) {
      this.active = false;
      return;
    }

    this.typeControl.forEach((control) =>
      control.addEventListener('click', () => this.toggleCompanyFields()),
    );
  }

  private toggleCompanyFields(): void {
    if (!this.active) {
      return;
    }
    const type =
      this.typeControl[0].type == 'radio'
        ? [...this.typeControl].filter((control) => control.checked)[0].value
        : this.typeControl[0].value;

    document
      .querySelectorAll<HTMLDivElement>('.hide-if-person')
      .forEach((holder) => {
        const input = holder.querySelector<HTMLInputElement>('input');

        holder.classList.toggle('shown', type != 'person');
        input.required = type == 'person';
        input.disabled = type == 'person';
      });
  }
}
