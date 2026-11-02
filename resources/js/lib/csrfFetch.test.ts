import { afterEach, expect, test, vi } from 'vitest';
import { csrfFetch } from './csrfFetch';

afterEach(() => {
    vi.restoreAllMocks();
    document.head.innerHTML = '';
});

test('adds the Laravel CSRF header to unsafe requests', async () => {
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">';
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(null, { status: 202 }));

    await csrfFetch('/ai/create', { method: 'POST', body: JSON.stringify({ input: 'synthetic prompt' }) });

    const requestHeaders = new Headers(fetchMock.mock.calls[0][1]?.headers);
    expect(requestHeaders.get('X-CSRF-TOKEN')).toBe('test-token');
});

test('does not send an unsafe request when the CSRF token is missing', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch');

    await expect(csrfFetch('/ai/create', { method: 'POST' })).rejects.toThrow('The security token is missing.');
    expect(fetchMock).not.toHaveBeenCalled();
});
