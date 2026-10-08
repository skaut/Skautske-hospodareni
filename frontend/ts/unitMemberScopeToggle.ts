export function initializeUnitMemberScopeToggles(root: ParentNode = document): void {
    root.querySelectorAll<HTMLInputElement>('[data-unit-member-scope-toggle]').forEach((toggle) => {
        if (toggle.dataset.unitMemberScopeToggleInitialized === '1') {
            return;
        }

        const link = toggle.closest<HTMLAnchorElement>('[data-unit-member-scope-toggle-link]');
        if (link === null) {
            return;
        }

        toggle.dataset.unitMemberScopeToggleInitialized = '1';
        toggle.addEventListener('change', () => {
            window.location.assign(link.href);
        });
    });
}
