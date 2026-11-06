/**
 * UserAuthWidget — o "TUserAuth" (Login & Cadastro / Aluno).
 */
BuilderFlex.UserAuthWidget = class UserAuthWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'UserAuthWidget';
        Object.assign(this.props, {
            name: 'TUserAuth',
            width: 200,
            height: 42,
            loginText: 'Entrar',
            loginUrl: '/aluno/login',
            registerText: 'Cadastrar',
            registerUrl: '/aluno/cadastro',
            cursor: 'pointer'
        });
    }

    tagName() { return 'div'; }

    extraStyles() {
        return `display: flex; align-items: center; justify-content: flex-end; gap: 8px; font-family: inherit;`;
    }

    content() {
        const login = BuilderFlex.escape(this.props.loginText || 'Entrar');
        const reg = BuilderFlex.escape(this.props.registerText || 'Cadastrar');
        const loginUrl = BuilderFlex.escape(this.props.loginUrl || '/aluno/login');
        const regUrl = BuilderFlex.escape(this.props.registerUrl || '/aluno/cadastro');

        return `<a href="${loginUrl}" style="display:inline-flex; align-items:center; gap:4px; padding:6px 12px; border-radius:6px; background:transparent; border:1px solid rgba(255,255,255,0.25); color:#fff; font-size:12px; font-weight:600; text-decoration:none;">🔑 ${login}</a>`
             + `<a href="${regUrl}" style="display:inline-flex; align-items:center; gap:4px; padding:6px 14px; border-radius:6px; background:#00d284; border:1px solid #00d284; color:#0b0c10; font-size:12px; font-weight:700; text-decoration:none; box-shadow:0 2px 8px rgba(0,210,132,0.25);">✨ ${reg}</a>`;
    }
};

BuilderFlex.WidgetRegistry.register('UserAuthWidget', BuilderFlex.UserAuthWidget, {
    label: 'TUserAuth (Login & Cadastro)',
    icon: 'fa-user-circle',
    palette: true
});
