import { reactive, ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/UploadController';
import type { ImageResource } from '@/types/image';

export interface UploadItem {
    id: string;
    name: string;
    previewUrl: string;
    progress: number;
    status: 'uploading' | 'done' | 'error';
    result: ImageResource | null;
    error: string | null;
}

export interface UploadOptions {
    expires_in?: number | null;
}

/**
 * Reads Laravel's XSRF-TOKEN cookie so manual XHR uploads pass CSRF checks.
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function errorMessage(xhr: XMLHttpRequest): string {
    try {
        const body = JSON.parse(xhr.responseText);

        if (body.errors) {
            const first = Object.values(body.errors)[0];

            return Array.isArray(first) ? first[0] : String(first);
        }

        if (body.message) {
            return body.message;
        }
    } catch {
        // fall through to a generic message
    }

    return xhr.status === 413
        ? 'That file is too large.'
        : 'Upload failed. Please try again.';
}

let counter = 0;

/**
 * Uploads files one request each so every card gets its own live progress and
 * independent success/failure. Newest uploads appear first.
 */
export function useUploader(getOptions: () => UploadOptions = () => ({})) {
    const items = ref<UploadItem[]>([]);

    function upload(files: File[]): void {
        for (const file of files) {
            // reactive up front so the xhr callbacks mutate the proxy vue tracks
            const item = reactive<UploadItem>({
                id: `u${counter++}`,
                name: file.name,
                previewUrl: URL.createObjectURL(file),
                progress: 0,
                status: 'uploading',
                result: null,
                error: null,
            });

            items.value.unshift(item);
            send(item, file);
        }
    }

    function send(item: UploadItem, file: File): void {
        const options = getOptions();
        const body = new FormData();
        body.append('images[]', file);

        if (options.expires_in) {
            body.append('expires_in', String(options.expires_in));
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', store.url());
        xhr.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                item.progress = Math.round((event.loaded / event.total) * 100);
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                const data = JSON.parse(xhr.responseText);
                item.result = data.images[0] as ImageResource;
                item.progress = 100;
                item.status = 'done';
            } else {
                item.status = 'error';
                item.error = errorMessage(xhr);
            }
        };

        xhr.onerror = () => {
            item.status = 'error';
            item.error = 'Network error. Please try again.';
        };

        xhr.send(body);
    }

    function remove(id: string): void {
        const item = items.value.find((candidate) => candidate.id === id);

        if (item) {
            URL.revokeObjectURL(item.previewUrl);
        }

        items.value = items.value.filter((candidate) => candidate.id !== id);
    }

    return { items, upload, remove };
}
