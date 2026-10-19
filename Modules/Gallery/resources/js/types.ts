export interface Album {
    id: number;
    title_bn: string;
    title_en: string | null;
    description_bn: string | null;
    description_en: string | null;
    album_date: string | null;
    is_published: boolean;
    sort_order: number;
    photos_count: number;
    cover_url: string | null;
}

export interface Photo {
    id: number;
    url: string;
    thumbnail_url: string;
    caption_bn: string | null;
    sort_order: number;
}
