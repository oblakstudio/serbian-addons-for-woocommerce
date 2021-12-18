import { BillingField } from '../components/billing-field.component';

export default class Checkout {
  private billingField: BillingField;

  public init(): void {
    this.billingField = new BillingField();
  }

  public finalize(): void {
    console.log('checkout');
    this.billingField.run();
  }
}
