export interface ImageResource {
    public_id: string;
    url: string;
    direct_url: string;
    thumbnail_url: string;
    width: number | null;
    height: number | null;
    size: number;
    human_size: string;
    mime_type: string;
    media_type: string;
    title: string | null;
    views: number;
    created_at: string | null;
    expires_at: string | null;
    delete_url?: string;
}
