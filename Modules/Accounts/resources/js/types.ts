export interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    roles: string[];
    last_login_at: string | null;
}

export interface Role {
    id: number;
    name: string;
    label: string;
    is_system: boolean;
    permissions: string[];
    users_count?: number;
}
