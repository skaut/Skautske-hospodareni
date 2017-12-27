export type Role = {
    id: number;
    name: string;
    activeRoleId: number;
}

export type User = {
    roles: Role[];
    loggedIn: boolean;
    loginLink: string | null;
    logoutLink: string | null;
    activeRoleId: number | null;
};
