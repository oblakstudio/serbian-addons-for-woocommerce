import { BillingField } from '../components/billing-field.component';

export class EditAddress {
  private billingField: BillingField;

  public init(): void {
    this.billingField = new BillingField();
  }

  public finalize(): void {
    this.billingField.run();
  }
}
