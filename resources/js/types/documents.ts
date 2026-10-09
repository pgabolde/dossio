export interface ClientDocument {
    id: number;
    original_filename: string;
    mime_type: string;
    size: number;
    status: 'pending' | 'processing' | 'ready' | 'failed';
    created_at: string;
}
