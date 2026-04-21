import http from '@/api/http';

export interface CloneRepoResult {
    host: string;
    owner: string;
    repo: string;
    branch: string;
    directory: string;
}

export default (uuid: string, repoUrl: string, branch: string, directory: string): Promise<CloneRepoResult> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/clone-repo`, {
            repo_url: repoUrl,
            branch: branch || null,
            directory,
        })
            .then(({ data }) => resolve(data?.data ?? data))
            .catch(reject);
    });
};
