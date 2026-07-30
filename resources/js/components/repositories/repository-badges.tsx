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
            <Badge
                variant="secondary"
                title={profileStatusPresentation[status].description}
                aria-label={`${profileStatusPresentation[status].label}: ${profileStatusPresentation[status].description}`}
            >
                {profileStatusPresentation[status].label}
            </Badge>
            <Badge
                variant="outline"
                title={repositoryVisibilityPresentation[visibility].description}
                aria-label={`${repositoryVisibilityPresentation[visibility].label}: ${repositoryVisibilityPresentation[visibility].description}`}
            >
                {repositoryVisibilityPresentation[visibility].label}
            </Badge>
        </div>
    );
}
