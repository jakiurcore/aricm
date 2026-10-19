import type { ReactNode } from 'react';

export interface ExamSummary {
    id: number;
    name_bn: string;
    session_id: number;
    start_date: string | null;
    end_date: string | null;
    status: string;
    admit_card_released: boolean;
    classes: { id: number; name: string }[];
}

export interface ExamTab {
    id: string;
    /** Called at render time so the label follows the locale. */
    label: () => string;
    /** Lower comes first. Exams' own tabs use 10, 20, 30. */
    order: number;
    render: (context: { exam: ExamSummary }) => ReactNode;
}

const tabs: ExamTab[] = [];

/**
 * Other modules add a tab to the exam detail page by calling this from their dashboard script
 * (Fees does it for the "fee" tab). Exams never imports them.
 */
export function registerExamTab(tab: ExamTab): void {
    if (tabs.some((existing) => existing.id === tab.id)) {
        throw new Error(`[exams] tab "${tab.id}" is already registered`);
    }

    tabs.push(tab);
}

export function getExamTabs(): ExamTab[] {
    return [...tabs].sort((a, b) => a.order - b.order);
}
