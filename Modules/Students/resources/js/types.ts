export interface Student {
    id: number;
    student_id: string;
    session_id: number;
    class_id: number;
    section_id: number | null;
    class_name?: string;
    section_name?: string | null;
    session_name?: string;
    roll: number | null;
    name_bn: string;
    name_en: string | null;
    father_name: string | null;
    mother_name: string | null;
    guardian_name: string | null;
    guardian_phone: string | null;
    guardian_relation: string | null;
    date_of_birth: string;
    gender: 'male' | 'female';
    blood_group: string | null;
    religion: string | null;
    present_address: string | null;
    permanent_address: string | null;
    admission_date: string;
    status: string;
    residency_type: string;
    photo_url: string | null;
    must_change_password: boolean;
}

export const STATUSES = ['active', 'inactive', 'transferred', 'passed_out'] as const;
export const RESIDENCY = ['non_residential', 'residential', 'day_care'] as const;
export const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as const;
