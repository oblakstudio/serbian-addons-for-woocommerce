const $ = jQuery;

export class OrderEditController {
  init(): void {}

  finalize(): void {
    $(document.body).on('click', 'button.wcsrb-copy-ips-qr', (e) => this.copyIpsQrCode(e));
  }

  private copyIpsQrCode(e: JQuery.ClickEvent): void {
    e.preventDefault();
    navigator.clipboard.writeText($(e.target).data('ips').s).then(() => {
      $(e.target).parent().find('.ips-qr-copy-success').fadeIn('fast').delay(800).fadeOut('fast');
    });
  }
}
