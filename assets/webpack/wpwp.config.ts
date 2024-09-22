import type { WordPackConfig } from '@x-wp/wordpack';

const config: Partial<WordPackConfig> = {
  bundles: [
    {
      name: 'admin',
      files: ['./scripts/admin/admin.ts', './styles/admin.scss'],
    },
    {
      name: 'front',
      files: ['./scripts/frontend/main.ts', './styles/main.scss'],
    },
  ],
  paths: {
    scripts: { src: 'scripts', dist: 'js' },
    styles: { src: 'styles', dist: 'css' },
  },
};

export default config;
