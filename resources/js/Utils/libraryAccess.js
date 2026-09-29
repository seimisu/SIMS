export const buildLibraryTargets = ({
    availableAll,
    regionScopes,
    scholarships,
    programs,
}) => {
    if (availableAll) {
        return [{ target_type: "all", target_id: null }];
    }

    const geography = (regionScopes ?? []).flatMap((scope) =>
        scope.school_coverage?.id !== "specific"
            ? [{ target_type: "region", target_id: scope.region.id }]
            : (scope.schools ?? []).map((school) => ({
                  target_type: "school",
                  target_id: school.id,
              })),
    );

    const dimension = (type, values) =>
        values?.length
            ? values.map((item) => ({ target_type: type, target_id: item.id }))
            : [{ target_type: type, target_id: "all" }];

    return [
        ...geography,
        ...dimension("scholarship_program", scholarships),
        ...dimension("program", programs),
    ];
};

export const libraryAccessFromTargets = (targets, regionOptions, schoolOptions) => {
    const regionTargetIds = new Set(
        (targets ?? [])
            .filter((target) => target.target_type === "region" && target.target_id !== "all")
            .map((target) => String(target.target_id)),
    );
    const selectedSchoolIds = new Set(
        (targets ?? [])
            .filter((target) => target.target_type === "school")
            .map((target) => String(target.target_id)),
    );

    const schoolRegions = new Set(
        schoolOptions
            .filter((school) => selectedSchoolIds.has(String(school.id)))
            .map((school) => String(school.region_id)),
    );
    const selectedRegionIds = new Set([...regionTargetIds, ...schoolRegions]);

    return regionOptions
        .filter((region) => selectedRegionIds.has(String(region.id)))
        .map((region) => ({
            region,
            school_coverage: regionTargetIds.has(String(region.id))
                ? { id: "all", name: "All schools in this region" }
                : { id: "specific", name: "Specific schools" },
            schools: schoolOptions.filter(
                (school) =>
                    String(school.region_id) === String(region.id) &&
                    selectedSchoolIds.has(String(school.id)),
            ),
        }));
};
