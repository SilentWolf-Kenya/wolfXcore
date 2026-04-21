import http from '@/api/http';

export interface WxnNotification {
    id: number;
    title: string;
    body: string;
    type: 'info' | 'success' | 'warning' | 'danger';
    is_read: boolean;
    created_at: string;
}

export interface NotificationsResponse {
    notifications: WxnNotification[];
    unread_count: number;
}

export const getNotifications = (): Promise<NotificationsResponse> =>
    http.get('/api/client/wxn/notifications').then((r) => r.data);

export const markNotificationRead = (id: number): Promise<void> =>
    http.post(`/api/client/wxn/notifications/${id}/read`).then(() => undefined);

export const markAllNotificationsRead = (): Promise<void> =>
    http.post('/api/client/wxn/notifications/read-all').then(() => undefined);
