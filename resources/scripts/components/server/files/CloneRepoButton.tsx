import React, { useContext, useEffect, useRef, useState } from 'react';
import { ServerContext } from '@/state/server';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import cloneRepo from '@/api/server/files/cloneRepo';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import { useFlashKey } from '@/plugins/useFlash';
import { useStoreActions } from '@/state/hooks';
import { Actions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { WithClassname } from '@/components/types';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Dialog, DialogWrapperContext } from '@/components/elements/dialog';
import asDialog from '@/hoc/asDialog';

interface Values {
    repoUrl: string;
    branch: string;
}

const schema = object().shape({
    repoUrl: string()
        .required('A repository URL is required.')
        .matches(/^https?:\/\/.+/i, 'Must start with https:// (or http://).'),
    branch: string().max(255),
});

const CloneRepoDialog = asDialog({
    title: 'Clone From Repository',
    description: 'Paste a public Git repository URL to fetch its files into the current directory.',
})(() => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const directory = ServerContext.useStoreState((state) => state.files.directory);

    const { mutate } = useFileManagerSwr();
    const { close } = useContext(DialogWrapperContext);
    const { addFlash, clearAndAddHttpError, clearFlashes } = useFlashKey('files:clone-modal');
    // Global flash store used for the post-close "Done" toast on the file-manager screen.
    const filesAddFlash = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes.addFlash);
    const filesClearFlashes = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes.clearFlashes);

    useEffect(() => {
        return () => {
            clearAndAddHttpError();
        };
    }, []);

    const stageTimers = useRef<number[]>([]);
    const showStage = (title: string, message: string) => {
        clearFlashes();
        addFlash({ type: 'info', title, message });
    };

    const submit = ({ repoUrl, branch }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();
        let host = '';
        try { host = new URL(repoUrl.trim()).host; } catch (_) { /* validation will catch */ }

        // Staged progress: Fetching → Extracting → Done. The actual operation is one
        // synchronous server call; the second message kicks in after a short delay so
        // long clones show realistic feedback instead of a single "Working…" line.
        showStage('Fetching…', host ? `Downloading the archive from ${host}.` : 'Downloading the archive.');
        stageTimers.current.push(window.setTimeout(() => {
            showStage('Extracting…', 'Unpacking the archive into your server files.');
        }, 2500));

        cloneRepo(uuid, repoUrl.trim(), branch.trim(), directory)
            .then((result) => {
                stageTimers.current.forEach(window.clearTimeout);
                stageTimers.current = [];
                clearFlashes();
                mutate();
                close();
                // Surface success on the file-manager screen via the global flash store
                filesClearFlashes('files');
                filesAddFlash({
                    key: 'files',
                    type: 'success',
                    title: 'Done',
                    message: `Cloned ${result.owner}/${result.repo} (${result.branch}) into ${result.directory}`,
                });
            })
            .catch((error) => {
                stageTimers.current.forEach(window.clearTimeout);
                stageTimers.current = [];
                setSubmitting(false);
                clearAndAddHttpError(error);
            });
    };

    useEffect(() => {
        return () => {
            stageTimers.current.forEach(window.clearTimeout);
            stageTimers.current = [];
        };
    }, []);

    return (
        <Formik onSubmit={submit} validationSchema={schema} initialValues={{ repoUrl: '', branch: '' }}>
            {({ submitForm, isSubmitting }) => (
                <>
                    <FlashMessageRender key={'files:clone-modal'} />
                    <Form css={tw`m-0`}>
                        <Field
                            autoFocus
                            id={'repoUrl'}
                            name={'repoUrl'}
                            label={'Repository URL'}
                            placeholder={'https://github.com/owner/repo'}
                        />
                        <div css={tw`mt-4`}>
                            <Field
                                id={'branch'}
                                name={'branch'}
                                label={'Branch (optional)'}
                                placeholder={'main'}
                            />
                            <p css={tw`mt-2 text-xs text-neutral-400`}>
                                Leave blank to auto-detect (tries <code>main</code>, then <code>master</code>). Only public repositories are supported.
                            </p>
                        </div>
                        <p css={tw`mt-4 text-xs text-neutral-300 break-all`}>
                            Files will be extracted into:&nbsp;
                            <code css={tw`text-cyan-200`}>/home/container{directory.replace(/\/+$/, '') || ''}</code>
                        </p>
                    </Form>
                    <Dialog.Footer>
                        <Button.Text className={'w-full sm:w-auto'} onClick={close} disabled={isSubmitting}>
                            Cancel
                        </Button.Text>
                        <Button className={'w-full sm:w-auto'} onClick={submitForm} disabled={isSubmitting}>
                            {isSubmitting ? 'Cloning…' : 'Clone'}
                        </Button>
                    </Dialog.Footer>
                </>
            )}
        </Formik>
    );
});

export default ({ className }: WithClassname) => {
    const [open, setOpen] = useState(false);

    return (
        <>
            <CloneRepoDialog open={open} onClose={setOpen.bind(this, false)} />
            <Button.Text onClick={setOpen.bind(this, true)} className={className}>
                {/* Inline branch icon — matches the spec without pulling in a new icon dependency. */}
                <svg
                    xmlns={'http://www.w3.org/2000/svg'}
                    viewBox={'0 0 24 24'}
                    fill={'none'}
                    stroke={'currentColor'}
                    strokeWidth={2}
                    strokeLinecap={'round'}
                    strokeLinejoin={'round'}
                    css={tw`w-4 h-4 mr-2 inline-block`}
                    aria-hidden
                >
                    <line x1={'6'} y1={'3'} x2={'6'} y2={'15'} />
                    <circle cx={'18'} cy={'6'} r={'3'} />
                    <circle cx={'6'} cy={'18'} r={'3'} />
                    <path d={'M18 9a9 9 0 0 1-9 9'} />
                </svg>
                Clone from Repo
            </Button.Text>
        </>
    );
};
