import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    History,
    MapPin,
    Paperclip,
    UserRound,
} from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    assign as assignComplaint,
    index as complaintsIndex,
} from '@/routes/complaints';
import { store as storeComment } from '@/routes/complaints/comments';
import { store as storeTransition } from '@/routes/complaints/transitions';

type Assignee = {
    id: number;
    name: string;
};

type Actor = {
    id: number;
    name: string;
};

type StatusHistoryEntry = {
    id: number;
    from_status: string | null;
    status: string;
    note: string | null;
    actor: Actor | null;
    created_at: string | null;
};

type Comment = {
    id: number;
    author: Actor;
    body: string;
    created_at: string | null;
};

type Complaint = {
    id: number;
    tracking_id: string;
    category: string;
    title: string;
    description: string;
    status: string;
    location: string | null;
    room_no: string | null;
    block: string | null;
    student_name: string | null;
    registration_number: string | null;
    days_open: number;
    is_overdue: boolean;
    created_at: string;
    assignee: Assignee | null;
    assigned_at: string | null;
    image_url: string | null;
    status_history: StatusHistoryEntry[];
    comments: Comment[];
};

type EligibleAssignee = {
    id: number;
    name: string;
    type: string;
};

type TimelineItem =
    | {
          key: string;
          type: 'status';
          created_at: string;
          entry: StatusHistoryEntry;
      }
    | {
          key: string;
          type: 'comment';
          created_at: string;
          entry: Comment;
      };

export default function ComplaintShow({
    complaint,
    can_assign: canAssign,
    can_comment: canComment,
    eligible_assignees: eligibleAssignees = [],
    available_transitions: availableTransitions,
}: {
    complaint: Complaint;
    can_assign: boolean;
    can_comment: boolean;
    eligible_assignees?: EligibleAssignee[];
    available_transitions: string[];
}) {
    const timeline: TimelineItem[] = [
        ...complaint.status_history.map((entry) => ({
            key: `status-${entry.id}`,
            type: 'status' as const,
            created_at: entry.created_at ?? complaint.created_at,
            entry,
        })),
        ...complaint.comments.map((entry) => ({
            key: `comment-${entry.id}`,
            type: 'comment' as const,
            created_at: entry.created_at ?? complaint.created_at,
            entry,
        })),
    ].sort((left, right) => {
        const difference =
            new Date(left.created_at).getTime() -
            new Date(right.created_at).getTime();

        return difference !== 0
            ? difference
            : left.key.localeCompare(right.key);
    });

    return (
        <>
            <Head title={`${complaint.tracking_id} · ${complaint.title}`} />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="font-mono text-xs font-medium text-muted-foreground">
                            {complaint.tracking_id}
                        </p>
                        <Heading
                            title={complaint.title}
                            description="Complaint details, progress, and conversation history."
                        />
                    </div>

                    <Button asChild variant="outline">
                        <Link href={complaintsIndex()}>
                            <ArrowLeft />
                            Back to complaints
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">
                        {formatValue(complaint.status)}
                    </Badge>
                    <Badge variant="outline">
                        {formatValue(complaint.category)}
                    </Badge>
                    {complaint.is_overdue && (
                        <Badge variant="destructive">Overdue</Badge>
                    )}
                    <Badge variant="outline">
                        {complaint.days_open}{' '}
                        {complaint.days_open === 1 ? 'day' : 'days'} open
                    </Badge>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Complaint details</h2>
                        <dl className="mt-3 space-y-3 text-sm">
                            <Detail
                                label="Tracking ID"
                                value={complaint.tracking_id}
                            />
                            <Detail
                                label="Submitted"
                                value={formatDateTime(complaint.created_at)}
                            />
                            <Detail
                                label="Category"
                                value={formatValue(complaint.category)}
                            />
                            <Detail
                                label="Status"
                                value={formatValue(complaint.status)}
                            />
                            <Detail
                                label="Student"
                                value={
                                    complaint.student_name
                                        ? `${complaint.student_name}${
                                              complaint.registration_number
                                                  ? ` (${complaint.registration_number})`
                                                  : ''
                                          }`
                                        : null
                                }
                            />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-2">
                            <MapPin className="size-4 text-muted-foreground" />
                            <h2 className="font-medium">Location</h2>
                        </div>
                        <div className="mt-3 text-sm">
                            {complaint.room_no ? (
                                <>
                                    <p className="font-medium">
                                        Room {complaint.room_no}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {complaint.block ?? 'Block unassigned'}
                                    </p>
                                </>
                            ) : (
                                <p className="text-muted-foreground">
                                    {complaint.location ??
                                        'No location recorded.'}
                                </p>
                            )}
                        </div>
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-2">
                            <UserRound className="size-4 text-muted-foreground" />
                            <h2 className="font-medium">Assignment</h2>
                        </div>
                        <div className="mt-3 text-sm">
                            {complaint.assignee ? (
                                <>
                                    <p className="font-medium">
                                        {complaint.assignee.name}
                                    </p>
                                    {complaint.assigned_at && (
                                        <p className="text-muted-foreground">
                                            Assigned{' '}
                                            {formatDateTime(
                                                complaint.assigned_at,
                                            )}
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p className="text-muted-foreground">
                                    This complaint is not assigned yet.
                                </p>
                            )}
                        </div>

                        {canAssign && (
                            <AssignComplaintForm
                                complaintId={complaint.id}
                                assignees={eligibleAssignees}
                                currentAssigneeId={
                                    complaint.assignee?.id ?? null
                                }
                            />
                        )}
                    </section>
                </div>

                <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 className="font-medium">Description</h2>
                    <p className="mt-3 text-sm whitespace-pre-wrap">
                        {complaint.description}
                    </p>
                </section>

                {complaint.image_url && (
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-2">
                            <Paperclip className="size-4 text-muted-foreground" />
                            <h2 className="font-medium">Attachment</h2>
                        </div>
                        <a
                            href={complaint.image_url}
                            target="_blank"
                            rel="noreferrer"
                            className="mt-3 inline-block text-sm underline underline-offset-4"
                        >
                            Open private attachment
                        </a>
                        <img
                            src={complaint.image_url}
                            alt="Complaint attachment"
                            className="mt-3 max-h-80 rounded-lg border border-sidebar-border/70 object-contain dark:border-sidebar-border"
                        />
                    </section>
                )}

                {availableTransitions.length > 0 && (
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Update status</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Only transitions permitted for your role and this
                            complaint are shown.
                        </p>
                        <TransitionForm
                            key={complaint.status}
                            complaintId={complaint.id}
                            currentStatus={complaint.status}
                            transitions={availableTransitions}
                        />
                    </section>
                )}

                <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex items-center gap-2">
                        <History className="size-4 text-muted-foreground" />
                        <h2 className="font-medium">Timeline</h2>
                    </div>

                    {timeline.length === 0 ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            No comments or status changes have been recorded
                            yet.
                        </p>
                    ) : (
                        <ol className="mt-4 space-y-4">
                            {timeline.map((item) => (
                                <li
                                    key={item.key}
                                    className="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                                >
                                    {item.type === 'status' ? (
                                        <StatusTimelineEntry
                                            entry={item.entry}
                                        />
                                    ) : (
                                        <CommentTimelineEntry
                                            entry={item.entry}
                                        />
                                    )}
                                </li>
                            ))}
                        </ol>
                    )}

                    {canComment && <CommentForm complaintId={complaint.id} />}
                </section>
            </div>
        </>
    );
}

function StatusTimelineEntry({ entry }: { entry: StatusHistoryEntry }) {
    return (
        <div className="text-sm">
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant="outline">Status</Badge>
                <span className="font-medium">
                    {entry.from_status
                        ? `${formatValue(entry.from_status)} → ${formatValue(
                              entry.status,
                          )}`
                        : formatValue(entry.status)}
                </span>
                <span className="text-muted-foreground">
                    by {entry.actor?.name ?? 'System'}
                </span>
            </div>
            <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                <Clock className="size-3" />
                {entry.created_at ? formatDateTime(entry.created_at) : '—'}
            </p>
            {entry.note && <p className="mt-2">{entry.note}</p>}
        </div>
    );
}

function CommentTimelineEntry({ entry }: { entry: Comment }) {
    return (
        <div className="text-sm">
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant="secondary">Comment</Badge>
                <span className="font-medium">{entry.author.name}</span>
                <span className="text-muted-foreground">
                    {entry.created_at ? formatDateTime(entry.created_at) : '—'}
                </span>
            </div>
            <p className="mt-2 whitespace-pre-wrap">{entry.body}</p>
        </div>
    );
}

function TransitionForm({
    complaintId,
    currentStatus,
    transitions,
}: {
    complaintId: number;
    currentStatus: string;
    transitions: string[];
}) {
    const form = useForm({
        status: transitions[0] ?? '',
        note: '',
    });
    const requiresNote =
        form.data.status === 'rejected' || form.data.status === 'resolved';

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(storeTransition.url(complaintId), {
            preserveScroll: true,
        });
    }

    return (
        <form onSubmit={submit} className="mt-4 space-y-3">
            <div className="grid gap-2">
                <Label htmlFor="transition_status">New status</Label>
                <select
                    id="transition_status"
                    value={form.data.status}
                    onChange={(event) =>
                        form.setData('status', event.target.value)
                    }
                    aria-invalid={Boolean(form.errors.status)}
                    className="flex h-9 w-full max-w-sm rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
                >
                    {transitions.map((status) => (
                        <option key={status} value={status}>
                            {formatValue(status)}
                        </option>
                    ))}
                </select>
                <InputError message={form.errors.status} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="transition_note">
                    {requiresNote
                        ? noteLabel(form.data.status)
                        : 'Note (optional)'}
                </Label>
                <textarea
                    id="transition_note"
                    rows={3}
                    value={form.data.note}
                    required={requiresNote}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                    aria-invalid={Boolean(form.errors.note)}
                    className="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                />
                <InputError message={form.errors.note} />
            </div>

            <Button
                type="submit"
                disabled={
                    form.processing ||
                    (requiresNote && form.data.note.trim() === '')
                }
            >
                {form.processing
                    ? 'Updating...'
                    : transitionActionLabel(form.data.status, currentStatus)}
            </Button>
        </form>
    );
}

function noteLabel(status: string) {
    if (status === 'rejected') {
        return 'Rejection reason';
    }

    if (status === 'resolved') {
        return 'Resolution note';
    }

    return 'Note';
}

function transitionActionLabel(status: string, currentStatus: string) {
    switch (status) {
        case 'under_review':
            return 'Move to under review';
        case 'in_progress':
            return currentStatus === 'resolved'
                ? 'Reopen complaint'
                : 'Mark in progress';
        case 'resolved':
            return 'Resolve complaint';
        case 'closed':
            return 'Close complaint';
        case 'rejected':
            return 'Reject complaint';
        default:
            return 'Update status';
    }
}

function AssignComplaintForm({
    complaintId,
    assignees,
    currentAssigneeId,
}: {
    complaintId: number;
    assignees: EligibleAssignee[];
    currentAssigneeId: number | null;
}) {
    const canKeepCurrent =
        currentAssigneeId !== null &&
        assignees.some((assignee) => assignee.id === currentAssigneeId);
    const form = useForm({
        assignee_id:
            canKeepCurrent && currentAssigneeId !== null
                ? String(currentAssigneeId)
                : assignees[0]
                  ? String(assignees[0].id)
                  : '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(assignComplaint.url(complaintId), {
            preserveScroll: true,
        });
    }

    return (
        <form onSubmit={submit} className="mt-4 space-y-3 border-t pt-4">
            <div className="grid gap-2">
                <Label htmlFor="assignee_id">Assign complaint</Label>
                <select
                    id="assignee_id"
                    value={form.data.assignee_id}
                    onChange={(event) =>
                        form.setData('assignee_id', event.target.value)
                    }
                    aria-invalid={Boolean(form.errors.assignee_id)}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
                >
                    <option value="" disabled>
                        Select an assignee
                    </option>
                    {assignees.map((assignee) => (
                        <option key={assignee.id} value={assignee.id}>
                            {assignee.name} ({formatValue(assignee.type)})
                        </option>
                    ))}
                </select>
                <p className="text-xs text-muted-foreground">
                    Only active House Tutors and Hall Office Staff can be
                    assigned.
                </p>
                <InputError message={form.errors.assignee_id} />
            </div>

            <Button
                type="submit"
                size="sm"
                disabled={form.processing || !form.data.assignee_id}
            >
                {form.processing ? 'Assigning...' : 'Assign'}
            </Button>
        </form>
    );
}

function CommentForm({ complaintId }: { complaintId: number }) {
    const form = useForm({ body: '' });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(storeComment.url(complaintId), {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    }

    return (
        <form onSubmit={submit} className="mt-4 space-y-3 border-t pt-4">
            <div className="grid gap-2">
                <Label htmlFor="body">Add a comment</Label>
                <textarea
                    id="body"
                    rows={3}
                    value={form.data.body}
                    onChange={(event) =>
                        form.setData('body', event.target.value)
                    }
                    aria-invalid={Boolean(form.errors.body)}
                    placeholder="Share an update with the people handling this complaint."
                    className="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                />
                <InputError message={form.errors.body} />
            </div>

            <Button
                type="submit"
                size="sm"
                disabled={form.processing || form.data.body.trim() === ''}
            >
                {form.processing ? 'Posting...' : 'Post comment'}
            </Button>
        </form>
    );
}

function Detail({
    label,
    value,
}: {
    label: string;
    value: string | null | undefined;
}) {
    return (
        <div>
            <dt className="text-xs font-medium text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-0.5">{value ?? '—'}</dd>
        </div>
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
