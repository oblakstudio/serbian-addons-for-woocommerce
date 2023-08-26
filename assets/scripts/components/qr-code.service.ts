import QRCodeStyling from 'qr-code-styling';

export class QrCodeService {
  private holder: HTMLDivElement;

  constructor() {
    this.holder = document.querySelector('.qr-code-holder');
  }

  run(): void {
    const qrString: string[] = [];

    for (const [key, value] of Object.entries(
      JSON.parse(this.holder.dataset.ips),
    )) {
      qrString.push(`${key}:${value}`);
    }

    new QRCodeStyling({
      width: 160,
      height: 160,
      qrOptions: {
        errorCorrectionLevel: 'L',
      },
      margin: -15,
      type: 'canvas',
      data: qrString.join('|'),
      image:
        this.holder.dataset.image !== ''
          ? this.holder.dataset.image
          : undefined,
      dotsOptions: {
        color: this.holder.dataset.color,
        type: 'classy-rounded',
      },
      backgroundOptions: {
        color: '#fff',
      },
      imageOptions: {
        imageSize: 0.5,
        crossOrigin: 'anonymous',
        margin: 5,
        hideBackgroundDots: true,
      },
      cornersSquareOptions: {
        type: 'extra-rounded',
        color: this.holder.dataset.cornerColor,
      },
    })
      .getRawData('jpeg')
      .then((data) => this.createImage(data, this.holder));
  }

  private createImage(data: Blob, qrCodeHolder: HTMLDivElement): void {
    const urlCreator = window.URL || window.webkitURL;
    const imageUrl = urlCreator.createObjectURL(data);
    const img = document.createElement('img');
    img.src = imageUrl;

    qrCodeHolder.appendChild(img);
  }
}
