<?php
namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType search.
 *
 * This is a breakout of the old Bolt\Content class and serves two main purposes:
 *   * Maintain backward compatibility for Bolt\Content through the remainder of
 *     the 2.x development/release life-cycle
 *   * Attempt to break up former functionality into sections of code that more
 *     resembles Single Responsibility Principles
 *
 * These traits should be considered transitional, the functionality in the
 * process of refactor, and not representative of a valid approach.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentSearchTrait
{
    /**
     * Weigh this content against a query.
     *
     * The query is assumed to be in a format as returned by decode Storage->decodeSearchQuery().
     *
     * @param array $query Query to weigh against
     *
     * @return void
     */
    public function weighSearchResult($query)
    {
        static $contenttypeFields = null;
        static $contenttypeTaxonomies = null;

        $ct = $this->contenttype['slug'];
        if ((is_null($contenttypeFields)) || (!isset($contenttypeFields[$ct]))) {
            // Should run only once per contenttype (e.g. singular_name)
            $contenttypeFields[$ct] = $this->getFieldWeights();
            $contenttypeTaxonomies[$ct] = $this->getTaxonomyWeights();
        }

        $weight = 0;

        // Go over all field, and calculate the overall weight.
        foreach ($contenttypeFields[$ct] as $key => $fieldWeight) {
            $value = $this->values[$key];
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $weight += $this->weighQueryText($value, $query['use_q'], $query['words'], $fieldWeight);
        }

        // Go over all taxonomies, and calculate the overall weight.
        foreach ($contenttypeTaxonomies[$ct] as $key => $taxonomy) {

            // skip empty taxonomies.
            if (empty($this->taxonomy[$key])) {
                continue;
            }
            $weight += $this->weighQueryText(implode(' ', $this->taxonomy[$key]), $query['use_q'], $query['words'], $taxonomy);
        }

        $this->lastWeight = $weight;
    }

    /**
     * Calculate the default field weights.
     *
     * This gives more weight to the 'slug pointer fields'.
     *
     * @return array
     */
    private function getFieldWeights()
    {
        // This could be more configurable
        // (see also Storage->searchSingleContentType)
        $searchableTypes = ['text', 'textarea', 'html', 'markdown'];

        $fields = [];

        foreach ($this->contenttype['fields'] as $key => $config) {
            if (in_array($config['type'], $searchableTypes)) {
                $fields[$key] = isset($config['searchweight']) ? $config['searchweight'] : 50;
            }
        }

        foreach ($this->contenttype['fields'] as $config) {
            if ($config['type'] === 'slug' && isset($config['uses'])) {
                foreach ((array) $config['uses'] as $ptrField) {
                    if (isset($fields[$ptrField])) {
                        $fields[$ptrField] = 100;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Calculate the default taxonomy weights.
     *
     * Adds weights to taxonomies that behave like tags.
     *
     * @return array
     */
    private function getTaxonomyWeights()
    {
        $taxonomies = [];

        if (isset($this->contenttype['taxonomy'])) {
            foreach ($this->contenttype['taxonomy'] as $key) {
                if ($this->app['config']->get('taxonomy/' . $key . '/behaves_like') === 'tags') {
                    $taxonomies[$key] = $this->app['config']->get('taxonomy/' . $key . '/searchweight', 75);
                }
            }
        }

        return $taxonomies;
    }
}
