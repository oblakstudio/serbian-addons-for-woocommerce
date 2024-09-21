import { TemplateExecutor } from 'lodash';

export class CompanySettingsController {
  private templates: Record<string, TemplateExecutor> = {};

  init(): void {
    document
      .querySelectorAll<HTMLElement>('.repeater-tmpl')
      .forEach((tmpl) => this.registerTemplate(tmpl));
  }

  finalize(): void {
    document
      .querySelectorAll<HTMLButtonElement>('.repeater-add-row')
      .forEach((btn) => btn.addEventListener('click', (e) => this.addRow(e)));

    document
      .querySelector('.bank-accounts')
      .addEventListener('click', (e) => this.removeRow(e));
  }

  private registerTemplate(tmpl: HTMLElement): void {
    this.templates[tmpl.id] = _.template(tmpl.innerHTML);
  }

  private addRow(event: Event): void {
    const btn = event.target as HTMLButtonElement;
    const { tmpl, ...template } = btn.dataset;
    const id = tmpl.replace('-tmpl', '');

    const wrapper = document.createElement('div');
    wrapper.innerHTML = this.templates[tmpl]({ data: template });

    document.querySelector(`#${id}`).appendChild(wrapper.querySelector('div'));
  }

  private removeRow(e: Event): void {
    const btn = e.target as HTMLButtonElement;

    if (!btn.classList.contains('repeater-remove-row')) {
      return;
    }

    const row = btn.closest('.row');
    row.parentNode.removeChild(row);
  }
}
