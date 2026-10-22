import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    ClipboardCheck,
    FolderGit2,
    LayoutGrid,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as blocks } from '@/routes/blocks';
import { index as registrations } from '@/routes/registrations';
import { index as students } from '@/routes/students';
import { index as users } from '@/routes/admin/users';
import type { Auth, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Registrations',
        href: registrations(),
        icon: ClipboardCheck,
        roles: ['super_admin', 'provost', 'hall_office_staff'],
    },
    {
        title: 'Students',
        href: students(),
        icon: Users,
        roles: [
            'super_admin',
            'provost',
            'hall_office_staff',
            'house_tutor',
            'student',
            'club_admin',
        ],
    },
    {
        title: 'Hall structure',
        href: blocks(),
        icon: Building2,
        roles: ['super_admin', 'provost', 'hall_office_staff'],
    },
    {
        title: 'Users',
        href: users(),
        icon: Users,
        roles: ['super_admin'],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const roles = auth.user?.roles ?? [];
    const visibleNavItems = mainNavItems.filter(
        (item) =>
            !item.roles || item.roles.some((role) => roles.includes(role)),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
