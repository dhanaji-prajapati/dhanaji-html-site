import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [tailwindcss()],
  server: {
    port: 3000,
    host: '0.0.0.0',
    hmr: process.env.DISABLE_HMR !== 'true',
    watch: process.env.DISABLE_HMR === 'true' ? null : {},
  },
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        seoServices: resolve(__dirname, 'seo-services/index.html'),
        seoTraining: resolve(__dirname, 'seo-training/index.html'),
        seoTrainingOneToOne: resolve(__dirname, 'seo-training/one-to-one/index.html'),
        seoTrainingGroup: resolve(__dirname, 'seo-training/group-training/index.html'),
        whiteLabelSeo: resolve(__dirname, 'white-label-seo/index.html'),
        pricing: resolve(__dirname, 'pricing/index.html'),
        about: resolve(__dirname, 'about/index.html'),
        portfolio: resolve(__dirname, 'portfolio/index.html'),
        blog: resolve(__dirname, 'blog/index.html'),
        contact: resolve(__dirname, 'contact/index.html'),
        conversionTracking: resolve(__dirname, 'seo-services/conversion-tracking/index.html'),
        reportingDashboards: resolve(__dirname, 'seo-services/reporting-dashboards/index.html'),
        gbpSuspension: resolve(__dirname, 'seo-services/gbp-suspension-recovery/index.html'),
        websiteMigration: resolve(__dirname, 'seo-services/website-migration/index.html'),
        noIndexCrawlability: resolve(__dirname, 'seo-services/no-index-crawlability-fixes/index.html'),
        penaltyRecovery: resolve(__dirname, 'seo-services/penalty-algorithm-recovery/index.html'),
        coreWebVitals: resolve(__dirname, 'seo-services/core-web-vitals/index.html'),
        freeSiteReview: resolve(__dirname, 'seo-services/free-site-review/index.html'),
        privacyPolicy: resolve(__dirname, 'privacy-policy/index.html'),
      },
    },
  },
});
