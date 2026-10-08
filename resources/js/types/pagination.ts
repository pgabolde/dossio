export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        links: PaginationLink[];
    };
    links: {
        prev: string | null;
        next: string | null;
    };
}
