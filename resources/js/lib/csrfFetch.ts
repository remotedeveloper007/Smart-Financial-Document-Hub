const safeMethods = new Set(['GET', 'HEAD', 'OPTIONS']);

export async function csrfFetch(input: RequestInfo | URL, init: RequestInit = {}): Promise<Response> {
    const method = (init.method ?? 'GET').toUpperCase();
    const headers = new Headers(init.headers);

    if (!safeMethods.has(method)) {
        const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

        if (!token) {
            throw new Error('The security token is missing. Refresh the page and try again.');
        }

        headers.set('X-CSRF-TOKEN', token);
    }

    return fetch(input, { ...init, headers });
}
