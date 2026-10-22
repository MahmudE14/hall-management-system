import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Paperclip, Pin } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as noticesIndex } from '@/routes/notices';

type PublicNotice = {
    id: number;
    title: string;
    body: string;
    category: string;
    is_pinned: boolean;
    published_at: string | null;
    created_at: string;
    has_attachment: boolean;
    creator: {
        id: number;
        name: string;
    };
};

export default function PublicNoticeShow({ notice }: { notice: PublicNotice }) {
    return (
        <>
            <Head title={notice.title} />

            <div className="space-y-6">
                <Button asChild variant="outline">
                    <Link href={noticesIndex()}>
                        <ArrowLeft />
                        Back to notice board
                    </Link>
                </Button>

                <article className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">
                            {formatValue(notice.category)}
                        </Badge>
                        {notice.is_pinned && (
                            <Badge variant="secondary">
                                <Pin className="size-3" />
                                Pinned
                            </Badge>
                        )}
                        {notice.has_attachment && (
                            <Badge variant="outline">
                                <Paperclip className="size-3" />
                                PDF attachment
                            </Badge>
                        )}
                    </div>

                    <Heading title={notice.title} />

                    <p className="text-sm text-muted-foreground">
                        {notice.published_at
                            ? `Published ${formatDateTime(notice.published_at)}`
                            : 'Published'}
                        {' · '}
                        {notice.creator.name}
                    </p>

                    <div className="mt-6 text-sm leading-relaxed whitespace-pre-wrap">
                        {notice.body}
                    </div>
                </article>
            </div>
        </>
    );
}

function formatValue(value: string) {
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatDateTime(value: string) {
    return new Date(value).toLocaleString();
}
