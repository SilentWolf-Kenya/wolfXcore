import http from '@/api/http';

export interface RegisterData {
    username: string;
    email: string;
    nameFirst: string;
    nameLast: string;
    password: string;
    passwordConfirmation: string;
}

export default (data: RegisterData): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register', {
                    username: data.username,
                    email: data.email,
                    name_first: data.nameFirst,
                    name_last: data.nameLast,
                    password: data.password,
                    password_confirmation: data.passwordConfirmation,
                })
            )
            .then((response) => resolve(response.data.message || 'Account created successfully.'))
            .catch(reject);
    });
};
