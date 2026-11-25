export class SlipSettingsController {
  model: HTMLSelectElement;
  private ref: HTMLInputElement;
  private btns: NodeListOf<HTMLAnchorElement>;

  init(): void {
    this.model = document.querySelector(
      '#woocommerce_wcsrb_payment_slip_payment_model',
    );
    this.ref = document.querySelector(
      '#woocommerce_wcsrb_payment_slip_payment_reference',
    );
    this.btns = document.querySelectorAll('.button.replacement');
  }

  finalize(): void {
    this.model.addEventListener('change', () => this.setPaymentReference(true));
    this.btns.forEach((btn) =>
      btn.addEventListener('click', (e) => this.toggleReplacement(e)),
    );

    // On load keep the saved reference - only a model change resets it.
    this.setPaymentReference(false);
  }

  /**
   * Locks the reference for model 97, unlocks it for the other models.
   *
   * @param reset Replace a custom reference with the automatic one (when the model changes).
   */
  private setPaymentReference(reset: boolean): void {
    if (this.model.value !== 'mod97') {
      if (reset) {
        this.ref.value = this.ref.dataset.auto;
      }
      this.btns.forEach((btn) => btn.classList.remove('disabled'));
      this.ref.readOnly = false;
      this.toggleClasses();
      return;
    }

    this.ref.value = this.ref.dataset.mod97;
    this.btns.forEach((btn) => btn.classList.add('disabled'));
    this.ref.readOnly = true;
    this.toggleClasses();
  }

  private toggleReplacement(e: Event): void {
    const btn = e.currentTarget as HTMLAnchorElement;
    const replacement = btn.dataset.code;

    e.preventDefault();

    if (btn.classList.contains('disabled')) {
      return;
    }

    let replacements = this.ref.value.split('-').filter((item) => item);

    if (replacements.includes(replacement)) {
      replacements = replacements
        .filter((item) => item !== replacement)
        .filter((item) => item !== '');
    } else {
      replacements.push(replacement);
    }

    btn.classList.toggle('active');
    this.ref.value = replacements.join('-');
  }

  private toggleClasses(): void {
    const replacements = this.ref.value.split('-');

    this.btns.forEach((btn) => {
      const replacement = btn.dataset.code;

      if (replacements.includes(replacement)) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }
}
