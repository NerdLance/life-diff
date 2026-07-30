import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';

export function CopyPublicLink({ href }: { href: string }) {
    const [copiedText, copy] = useClipboard();

    return (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                onClick={() =>
                    void copy(new URL(href, window.location.origin).toString())
                }
            >
                Copy link
            </Button>
            <span className="sr-only" aria-live="polite">
                {copiedText ? 'Public link copied.' : ''}
            </span>
        </div>
    );
}
