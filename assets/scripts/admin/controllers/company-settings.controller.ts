export class CompanySettingsController {
  private templates: Record<string, _.CompiledTemplate> = {};

  init(): void {
    this.registerTemplates('woocommerce_store_bank_accounts');
  }

  finalize(): void {
    document
      .querySelectorAll<HTMLButtonElement>('.repeater-add-row')
      .forEach((btn) =>
        btn.addEventListener('click', ({ target }) =>
          this.addRow(target as HTMLButtonElement),
        ),
      );

    document
      .querySelector('.bank-accounts')
      .addEventListener('click', ({ target }) =>
        this.removeRow(target as HTMLButtonElement),
      );
  }

  private registerTemplates(...ids: string[]): void {
    ids.forEach((id) => {
      this.templates[id] = window.wp.template(id);
    });
  }

  private addRow(btn: HTMLButtonElement): void {
    const { tmpl, ...data } = btn.dataset;

    document
      .querySelector(`#${tmpl}`)
      .insertAdjacentHTML('beforeend', this.templates[tmpl](data));
  }

  private removeRow(btn: HTMLButtonElement): void {
    if (!btn.classList.contains('repeater-remove-row')) {
      return;
    }

    const row = btn.closest('.row');
    row.parentNode.removeChild(row);
  }
}
