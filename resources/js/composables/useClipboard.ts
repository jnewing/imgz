import { toast } from 'vue-sonner';

export function useClipboard() {
    async function copy(
        text: string,
        label = 'Copied to clipboard',
    ): Promise<void> {
        try {
            await navigator.clipboard.writeText(text);
            toast.success(label);
        } catch {
            toast.error('Could not copy to clipboard');
        }
    }

    return { copy };
}
