declare global {
  const Backbone: typeof Backbone;
  const _: _.UnderscoreStatic;

  interface Window {
    wc_address_i18n_params: any;
    wp: {
      template: (id: string) => _.CompiledTemplate;
    };
  }
}

export {};
