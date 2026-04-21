import React, { useEffect } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import { Formik, FormikHelpers } from 'formik';
import { object, string, ref as yupRef } from 'yup';
import Field from '@/components/elements/Field';
import Button from '@/components/elements/Button';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import useFlash from '@/plugins/useFlash';
import register, { RegisterData } from '@/api/auth/register';
import { httpErrorToHuman } from '@/api/http';
import tw from 'twin.macro';

const RegisterContainer = ({ history }: RouteComponentProps) => {
    const { clearFlashes, addFlash } = useFlash();

    useEffect(() => {
        clearFlashes();
    }, []);

    const onSubmit = (values: RegisterData, { setSubmitting, resetForm }: FormikHelpers<RegisterData>) => {
        clearFlashes();

        register(values)
            .then((message) => {
                resetForm();
                addFlash({ type: 'success', title: 'Account Created', message });
                setTimeout(() => history.push('/auth/login'), 2500);
            })
            .catch((error) => {
                console.error(error);
                setSubmitting(false);
                addFlash({ type: 'error', title: 'Registration Failed', message: httpErrorToHuman(error) });
            });
    };

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{
                username: '',
                email: '',
                nameFirst: '',
                nameLast: '',
                password: '',
                passwordConfirmation: '',
            }}
            validationSchema={object().shape({
                username: string()
                    .required('A username is required.')
                    .min(3, 'Username must be at least 3 characters.')
                    .max(32, 'Username cannot exceed 32 characters.')
                    .matches(/^[a-zA-Z0-9_\-\.]+$/, 'Username may only contain letters, numbers, underscores, hyphens and dots.'),
                email: string()
                    .email('A valid email address is required.')
                    .required('An email address is required.'),
                nameFirst: string()
                    .required('First name is required.')
                    .min(1, 'First name must be at least 1 character.'),
                nameLast: string()
                    .required('Last name is required.')
                    .min(1, 'Last name must be at least 1 character.'),
                password: string()
                    .required('A password is required.')
                    .min(8, 'Password must be at least 8 characters.'),
                passwordConfirmation: string()
                    .required('Please confirm your password.')
                    .oneOf([yupRef('password')], 'Passwords do not match.'),
            })}
        >
            {({ isSubmitting }) => (
                <LoginFormContainer title={'Create an Account'} css={tw`w-full flex`}>
                    <div css={tw`flex gap-3`}>
                        <div css={tw`flex-1`}>
                            <Field light type={'text'} label={'First Name'} name={'nameFirst'} disabled={isSubmitting} />
                        </div>
                        <div css={tw`flex-1`}>
                            <Field light type={'text'} label={'Last Name'} name={'nameLast'} disabled={isSubmitting} />
                        </div>
                    </div>
                    <div css={tw`mt-5`}>
                        <Field light type={'text'} label={'Username'} name={'username'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-5`}>
                        <Field light type={'email'} label={'Email Address'} name={'email'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-5`}>
                        <Field light type={'password'} label={'Password'} name={'password'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-5`}>
                        <Field
                            light
                            type={'password'}
                            label={'Confirm Password'}
                            name={'passwordConfirmation'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting} disabled={isSubmitting}>
                            Create Account
                        </Button>
                    </div>
                    <div css={tw`mt-5 text-center`}>
                        <Link
                            to={'/auth/login'}
                            css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                        >
                            Already have an account? Login
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default RegisterContainer;
