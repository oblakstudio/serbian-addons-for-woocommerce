import buildConfig from '@x-wp/wordpack';
import type { Configuration } from 'webpack';

export default async (env: Record<string, string>): Promise<Configuration[]> =>
  await buildConfig(env);
