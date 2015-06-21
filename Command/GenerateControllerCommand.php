<?php

namespace ERP\SwaggerBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use ERP\Swagger\Entity\Path;
use ERP\Swagger\Entity\Swagger;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateControllerCommand extends ContainerAwareCommand
{

    /**
     * Cli Input
     * @var InputInterface
     */
    protected $input;

    /**
     * Cli Output
     * @var OutputInterface
     */
    protected $output;

    /**
     * Swagger Doc
     * @var Swagger
     */
    protected $swagger;

    /**
     * Flattened Paths
     * @var array
     */
    protected $routes = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('swagger:generate:controllers')
            ->setDescription('Generate base REST controllers from swagger documentation')
            ->setDefinition(array(
                new InputOption('bundle', '', InputOption::VALUE_REQUIRED, 'The name of the bundle to generate controllers for'),
                new InputOption('controller', '', InputOption::VALUE_OPTIONAL, 'The name of the bundle to generate controllers for'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->swagger = $this->getContainer()->get('swagger_bundle.provider.swagger')->getSwagger();
        $this->routes  = $this->flattenPaths($this->swagger->getPaths());

        $bundle = $this->getBundleName();

        $this->output->writeln('<info>Bundle Selected: </info>'. $bundle);

        foreach ($this->routes as $key => $paths) {
            $this->buildController($bundle, $key, $paths);
        }

        var_dump($this->routes);
        exit;
    }

    /**
     * Get the target bundle name from user input
     *
     * @return string
     */
    protected function getBundleName()
    {
        $questionHelper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            '<question>Enter bundle name</question> (example: AcmeBundle): ',
            $this->getContainer()->getParameter('kernel.bundles'),
            $this->input->getOption('bundle')
        );

        $question->setValidator(array(Validators::class, 'validateBundleName'));

        return $questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * @param string $key
     * @param Path[]
     */
    protected function buildController($bundle, $key, array $paths)
    {
        $twig = $this->getContainer()->get('twig');

        $twig->createTemplate('controller.php.twig');
        $twig->mergeGlobals([
            'manspace' => $bundle,
            'paths'    => $paths
        ]);

        $controllerContent = $twig->render('controller.php.twig', [
            'manspace' => $bundle,
            'paths'    => $paths
        ]);
    }

    /**
     * Parse and flatten all of the swagger paths
     * into controller base path names
     *
     * @param ArrayCollection|Path[] $paths
     * @return array
     */
    protected function flattenPaths(ArrayCollection $paths)
    {
        $routes = [];

        foreach ($paths as $key => $path) {
            $parts = explode('/', $key);
            $base  = $parts[1];

            if (!array_key_exists($base, $routes)) {
                $routes[$base] = [];
            }

            $routes[$base][] = $path;
        }

        return $routes;
    }
}
