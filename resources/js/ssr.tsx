import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) => {
            // If name starts with 'domains/', resolve from domains directory
            if (name.startsWith('domains/')) {
                return resolvePageComponent(
                    `./${name}.tsx`,
                    import.meta.glob('./domains/**/*.tsx', { eager: true }),
                );
            }
            // Otherwise, resolve from pages directory
            return resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx', { eager: true }),
            );
        },
        setup: ({ App, props }) => {
            return <App {...props} />;
        },
    }),
);
