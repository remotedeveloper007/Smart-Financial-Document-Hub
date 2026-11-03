import { router, usePoll } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const REFRESH_INTERVAL_MS = 30_000;

export default function useSystemHealth() {
    const [lastUpdatedAt, setLastUpdatedAt] = useState(() => new Date());
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [refreshError, setRefreshError] = useState<string | null>(null);

    const requestOptions = useMemo(
        () => ({
            only: ['summary'],
            preserveScroll: true,
            onStart: () => {
                setIsRefreshing(true);
                setRefreshError(null);
            },
            onSuccess: () => {
                setLastUpdatedAt(new Date());
                setRefreshError(null);
            },
            onError: () => {
                setRefreshError('System health could not be refreshed. Try again.');
            },
            onFinish: () => {
                setIsRefreshing(false);
            },
        }),
        [],
    );

    usePoll(REFRESH_INTERVAL_MS, requestOptions, { mode: 'rest' });

    useEffect(
        () =>
            router.on('exception', () => {
                setIsRefreshing(false);
                setRefreshError(
                    'System health could not be refreshed. Check your connection and try again.',
                );
            }),
        [],
    );

    return {
        lastUpdatedAt,
        isRefreshing,
        refreshError,
        refresh: () => router.reload(requestOptions),
    };
}
