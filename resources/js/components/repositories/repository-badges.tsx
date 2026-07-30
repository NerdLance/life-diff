import { Badge } from '@/components/ui/badge';
import {
    profileStatusPresentation,
    repositoryVisibilityPresentation,
} from '@/types';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

export function RepositoryBadges({
    status,
    visibility,
}: {
    status: ProfileStatus;
    visibility: RepositoryVisibility;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <Badge variant="secondary">
                {profileStatusPresentation[status].label}
            </Badge>
            <Badge variant="outline">
                {repositoryVisibilityPresentation[visibility].label}
            </Badge>
        </div>
    );
}
