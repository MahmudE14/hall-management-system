# Architecture

The application is a modular monolith built on Laravel 13, PHP 8.4, MySQL, Inertia React,
TypeScript, Tailwind CSS, and Vite.

Use standard Laravel structure: routes, controllers, Form Requests, Policies, Eloquent models,
and focused services only when a concrete workflow needs one. Preserve Fortify authentication,
Wayfinder-generated routes/actions, and the existing shadcn/Radix components. Tests run against
isolated in-memory SQLite; local application data uses MySQL.
