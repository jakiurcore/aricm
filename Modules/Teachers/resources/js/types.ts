export interface Teacher {
    id: number;
    name_bn: string;
    name_en: string | null;
    designation: string | null;
    phone: string | null;
    email: string | null;
    joined_on: string | null;
    status: 'active' | 'leave' | 'inactive';
    photo_url: string | null;
    is_active: boolean;
    login_email: string | null;
    user_id: number | null;
    assignments_count: number;
}

/** "মাওলানা আব্দুল করিম" -> "আব্দুল করিম": the grid names a teacher without the honorific, in two words. */
export function shortName(name: string): string {
    return name.replace(/^((হাফেজ|মাওলানা|মুফতি|মোঃ|মোসাঃ)\s+)+/, '').split(' ').slice(0, 2).join(' ');
}
