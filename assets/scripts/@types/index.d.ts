declare global {
  const Backbone: typeof Backbone;
  const _: _.UnderscoreStatic;

  interface Window {
    wp: {
      template: (id: string) => _.CompiledTemplate;
    };
  }
}

export {};
