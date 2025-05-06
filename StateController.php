<?php

namespace App\Controller;

use App\Repository\StateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/state')]
class StateController extends AbstractController
{

    public function __construct(
        private StateRepository                    $stateRepository,
        private SerializerInterface                $serializer,
        #[Autowire(env: 'API_KEY')] private string $apiKey
    )
    {
    }

    #[Route('/{authenticate}', methods: ['POST'])]
    public function authenticate(Request $request)
    {
        if ($request->headers->get('x-api-key') !== $this->apiKey) {
            return new Response(status: Response::HTTP_UNAUTHORIZED);
        }

        return new Response(status: Response::HTTP_OK);
    }
    
    #[Route('/{country}/{zip}', methods: ['GET'])]
    #[Route('/{country}/{zip}/state', defaults: ['type' => 'state'], methods: ['GET'])]
    #[Route('/{country}/{zip}/abbreviation', defaults: ['type' => 'abbreviation'], methods: ['GET'])]
    public function state(
        Request $request,
        string  $country,
        string  $zip,
        ?string $type = null
    ): Response
    {
        if ($this->authenticate($request)->getStatusCode() === Response::HTTP_OK) {

            $state = $this->stateRepository->findOneByCountryAndZip($country, $zip);

            if ($state === null) {
                return new Response(status: Response::HTTP_NOT_FOUND);
            }

            $groups = [
                'all'
            ];
            if ($type !== null) {
                $groups = [$type];
            }
            return new Response(
                $this->serializer->serialize($state, JsonEncoder::FORMAT, [
                    AbstractNormalizer::GROUPS => $groups
                ]),
            );
        }

        return new Response(status: Response::HTTP_UNAUTHORIZED);
    }

}