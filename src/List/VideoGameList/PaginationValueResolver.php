<?php

declare(strict_types=1);

namespace App\List\VideoGameList;

use App\Model\ValueObject\Direction;
use App\Model\ValueObject\Sorting;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('pagination')]
final readonly class PaginationValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<int, Pagination>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();

        if ($argumentType !== Pagination::class) {
            return [];
        }

        // 1. On récupère la page et on s'assure qu'elle est >= 1
        $page = max(1, $request->query->getInt('page', 1));

        // 2. On récupère la limite et on force un minimum de 1 (évite le <= 0)
        $limit = max(1, $request->query->getInt('limit', 10));

        return [new Pagination(
            $page,
            $limit,
            Sorting::tryFromName($request->query->get('sorting', '')) ?? Sorting::ReleaseDate,
            Direction::tryFromName($request->query->get('direction', '')) ?? Direction::Descending,
        )];
    }
}
